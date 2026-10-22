<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Sends notifications to the phones that agreed to receive them (standard Web Push, so no
 * Google or Apple account is needed). The VAPID key pair is made once and kept encrypted in
 * the settings table; changing it would silently cut off every subscribed phone.
 */
class PushSender
{
    /** @return array{publicKey:string, privateKey:string} */
    public function keys(): array
    {
        $public = Setting::get('push.vapid_public');
        $private = Setting::get('push.vapid_private');

        if (! $public || ! $private) {
            [$public, $private] = $this->makeKeys();
            Setting::put('push.vapid_public', $public);
            Setting::put('push.vapid_private', $private);
        }

        return ['publicKey' => $public, 'privateKey' => $private];
    }

    /**
     * A P-256 key pair in the base64url form VAPID uses. Made with openssl directly so that
     * Windows PHP builds (which need their openssl.cnf named) work as well as the Linux server.
     *
     * @return array{0:string, 1:string} public, private
     */
    private function makeKeys(): array
    {
        $options = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $cnf = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';

        if (PHP_OS_FAMILY === 'Windows' && is_file($cnf)) {
            $options['config'] = $cnf;
        }

        $key = openssl_pkey_new($options);
        $details = $key ? openssl_pkey_get_details($key) : false;

        if (! $details || ! isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            $made = VAPID::createVapidKeys();

            return [(string) $made['publicKey'], (string) $made['privateKey']];
        }

        $b64 = fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        $pad = fn (string $bytes): string => str_pad($bytes, 32, "\0", STR_PAD_LEFT);

        return [
            $b64("\x04".$pad($details['ec']['x']).$pad($details['ec']['y'])),
            $b64($pad($details['ec']['d'])),
        ];
    }

    /** Null when this server cannot make keys (then the page explains instead of failing). */
    public function publicKey(): ?string
    {
        try {
            return $this->keys()['publicKey'];
        } catch (Throwable $exception) {
            Log::warning('Push keys unavailable: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * @param  iterable<User>  $users
     * @param  array<string, mixed>  $payload  title, body, url, tag, actions…
     * @return int phones reached
     */
    public function toUsers(iterable $users, array $payload): int
    {
        $ids = collect($users)->pluck('id')->unique()->all();

        if ($ids === []) {
            return 0;
        }

        // The bell in the app keeps every one, so a phone that missed it still finds it there.
        foreach ($ids as $id) {
            AppNotification::query()->create([
                'user_id' => $id,
                'title' => mb_substr((string) ($payload['title'] ?? 'মাটির খামার'), 0, 250),
                'body' => isset($payload['body']) ? (string) $payload['body'] : null,
                'url' => isset($payload['url']) ? mb_substr((string) $payload['url'], 0, 250) : null,
                'tag' => isset($payload['tag']) ? mb_substr((string) $payload['tag'], 0, 80) : null,
            ]);
        }

        $subscriptions = PushSubscription::query()->whereIn('user_id', $ids)->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $keys = $this->keys();
        $push = new WebPush(['VAPID' => [
            'subject' => 'mailto:'.(config('mail.from.address') ?: 'khamar@klokads.com'),
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ]], ['TTL' => 6 * 3600, 'urgency' => 'high']);

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $subscription) {
            $push->queueNotification(Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding,
            ]), $body);
        }

        $reached = 0;
        $byEndpoint = $subscriptions->keyBy('endpoint');

        try {
            foreach ($push->flush() as $report) {
                $subscription = $byEndpoint->get($report->getEndpoint());

                if ($subscription === null) {
                    continue;
                }

                if ($report->isSuccess()) {
                    $reached++;
                    $subscription->update(['failures' => 0, 'last_sent_at' => now()]);
                } elseif ($report->isSubscriptionExpired() || $subscription->failures >= 5) {
                    // The phone uninstalled the app or turned notifications off.
                    $subscription->delete();
                } else {
                    $subscription->increment('failures');
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Push sending failed: '.$exception->getMessage());
        }

        return $reached;
    }
}
