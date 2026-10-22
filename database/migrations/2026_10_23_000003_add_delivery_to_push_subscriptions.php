<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whether a phone really receives what is sent: the service worker reports each push it gets.
 * A phone that never reports a push with words in it is sent empty pushes instead ("tickle"),
 * on which it fetches the words from the app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->timestamp('last_received_at')->nullable()->after('last_sent_at');
            $table->string('mode', 10)->default('payload')->after('content_encoding');
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['last_received_at', 'mode']);
        });
    }
};
