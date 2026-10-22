<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A subscription is either a browser's (web push through the browser) or the farm's own
 * Android app's (a Firebase token, sent straight to the app like chat apps).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->string('kind', 10)->default('web')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
