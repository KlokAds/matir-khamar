<?php

use App\Enums\Role;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Services\FcmSender;
use App\Services\PushSender;
use Database\Seeders\KhamarSetupSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(KhamarSetupSeeder::class);
    $this->user = User::factory()->create(['role' => Role::Manager]);
    $this->actingAs($this->user);
});

it('keeps the farm app\'s Firebase token under the signed-in person', function () {
    $token = str_repeat('a', 40).':APA91b';

    $this->postJson('/push/app', ['token' => $token])->assertNoContent();
    $this->postJson('/push/app', ['token' => $token])->assertNoContent();

    $sub = PushSubscription::query()->sole();
    expect($sub->kind)->toBe('fcm')->and($sub->endpoint)->toBe($token)->and($sub->user_id)->toBe($this->user->id);
});

it('sends to the farm app through Firebase, and forgets an uninstalled app', function () {
    // Windows PHP builds need their openssl.cnf named; the Linux CI does not.
    $cnf = dirname(PHP_BINARY).'/extras/ssl/openssl.cnf';
    $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA] + (PHP_OS_FAMILY === 'Windows' && is_file($cnf) ? ['config' => $cnf] : []);
    $key = openssl_pkey_new($options);
    openssl_pkey_export($key, $pem, null, $options);
    Setting::put(FcmSender::SETTING, json_encode(['project_id' => 'matir-khamar', 'client_email' => 'x@matir-khamar.iam.gserviceaccount.com', 'private_key' => $pem]));

    foreach (['good-token-'.str_repeat('g', 20), 'gone-token-'.str_repeat('x', 20)] as $token) {
        PushSubscription::query()->create([
            'user_id' => $this->user->id, 'kind' => 'fcm', 'endpoint' => $token, 'endpoint_hash' => hash('sha256', $token),
            'public_key' => '', 'auth_token' => '', 'device' => 'Mozilla/5.0 (Linux; Android 10) MatirKhamarApp/3',
        ]);
    }

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test']),
        'fcm.googleapis.com/*' => fn ($request) => str_contains($request->body(), 'gone-token')
            ? Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404)
            : Http::response(['name' => 'projects/matir-khamar/messages/1']),
    ]);

    $reached = app(PushSender::class)->toUsers([$this->user], ['title' => 'দুধ লিখুন', 'body' => 'বিকালের দুধ', 'url' => '/milk', 'alarm' => true]);

    expect($reached)->toBe(1)->and(PushSubscription::query()->pluck('endpoint')->all())->toBe(['good-token-'.str_repeat('g', 20)]);

    // An alarm goes as data only, for the app to ring until touched.
    Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send')
        && ! isset($request['message']['notification'])
        && $request['message']['data']['alarm'] === '1'
        && $request['message']['data']['url'] === '/milk');

    app(PushSender::class)->toUsers([$this->user], ['title' => 'সকালের কাজ', 'url' => '/dashboard']);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send')
        && ($request['message']['notification']['title'] ?? null) === 'সকালের কাজ'
        && $request['message']['android']['notification']['channel_id'] === 'khamar_alerts');
});
