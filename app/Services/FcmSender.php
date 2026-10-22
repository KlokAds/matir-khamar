<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifications straight to the farm's own Android app through Firebase Cloud Messaging, the
 * way chat apps get theirs: a high-priority data message that the app turns into a loud,
 * pop-up notification on its own channel. The Firebase service account (a JSON key) is kept
 * encrypted in settings, never in the code.
 */
class FcmSender
{
    public const SETTING = 'fcm.service_account';

    /** @return array<string, string>|null */
    public function account(): ?array
    {
        $json = Setting::get(self::SETTING);
        $data = $json ? json_decode($json, true) : null;

        return is_array($data) && isset($data['project_id'], $data['client_email'], $data['private_key']) ? $data : null;
    }

    public function ready(): bool
    {
        return $this->account() !== null;
    }

    /**
     * @param  iterable<PushSubscription>  $subscriptions  the app's FCM tokens
     * @param  array<string, mixed>  $payload  title, body, url, tag, alarm, reminder_id…
     * @return int phones reached
     */
    public function send(iterable $subscriptions, array $payload): int
    {
        $account = $this->account();
        $token = $account ? $this->accessToken($account) : null;

        if ($account === null || $token === null) {
            return 0;
        }

        // Data only: the app builds the notification itself, so it can choose its channel,
        // sound and alarm, and it arrives the same whether the app is open or closed.
        $data = array_map(fn ($value) => is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE), array_filter($payload, fn ($v) => $v !== null));
        $reached = 0;

        foreach ($subscriptions as $subscription) {
            // An alarm goes as data only: the app itself shows it, ringing on until touched
            // (as an alarm clock does), with "done" and "later" buttons.
            // App 3 rings alarms by itself; app 4 also schedules reminders on the phone, so it
            // takes every reminder as data and skips one it already rang. Older apps are sent
            // a plain notification.
            $version = preg_match('/MatirKhamarApp\/(\d+)/', (string) $subscription->device, $m) === 1 ? (int) $m[1] : 0;
            $message = (! empty($payload['alarm']) && $version >= 3) || (isset($payload['reminder_id']) && $version >= 4)
                ? ['token' => $subscription->endpoint, 'data' => $data, 'android' => ['priority' => 'high', 'ttl' => '600s']]
                : null;

            try {
                $response = Http::withToken($token)->timeout(10)->post(
                    "https://fcm.googleapis.com/v1/projects/{$account['project_id']}/messages:send",
                    ['message' => $message ?? [
                        'token' => $subscription->endpoint,
                        'notification' => [
                            'title' => (string) ($payload['title'] ?? 'মাটির খামার'),
                            'body' => (string) ($payload['body'] ?? ''),
                        ],
                        'data' => $data,
                        // The app's "khamar_alerts" channel pops up with sound like a chat
                        // message. Alarms use it too: the alarm channel's own ring stayed silent on
                        // some phones, and an alarm already rings again every two minutes.
                        'android' => [
                            'priority' => 'high',
                            'ttl' => '21600s',
                            'notification' => array_filter([
                                'channel_id' => 'khamar_alerts',
                                'sound' => 'default',
                                'tag' => isset($payload['tag']) ? (string) $payload['tag'] : null,
                                'notification_priority' => 'PRIORITY_MAX',
                                'visibility' => 'PUBLIC',
                                'default_vibrate_timings' => true,
                                'icon' => 'ic_notification',
                                'color' => '#4A3726',
                            ], fn ($v) => $v !== null),
                        ],
                    ]],
                );

                if ($response->successful()) {
                    $reached++;
                    $subscription->update(['failures' => 0, 'last_sent_at' => now()]);
                } elseif (in_array($response->status(), [404, 410], true) || str_contains($response->body(), 'UNREGISTERED')) {
                    // The app was uninstalled or its token replaced.
                    $subscription->delete();
                } else {
                    $subscription->increment('failures');
                    Log::warning('FCM send failed: '.$response->status().' '.mb_substr($response->body(), 0, 300));
                }
            } catch (Throwable $exception) {
                Log::warning('FCM send failed: '.$exception->getMessage());
            }
        }

        return $reached;
    }

    /**
     * A short-lived OAuth token from the service account (a signed JWT exchanged at Google),
     * kept for 50 minutes.
     *
     * @param  array<string, string>  $account
     */
    private function accessToken(array $account): ?string
    {
        $cached = Cache::get('fcm.access_token');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = (function () use ($account): ?string {
            $now = time();
            $b64 = fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $header = $b64((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $b64((string) json_encode([
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $signature = '';

            if (! openssl_sign("{$header}.{$claims}", $signature, $account['private_key'], OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "{$header}.{$claims}.".$b64($signature),
            ]);

            return $response->successful() ? (string) $response->json('access_token') : null;
        })();

        if ($token !== null) {
            Cache::put('fcm.access_token', $token, 3000);
        }

        return $token;
    }
}
