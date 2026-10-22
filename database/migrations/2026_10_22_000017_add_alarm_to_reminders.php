<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alarm reminders keep ringing: the notification comes back every two minutes until someone
 * presses "হয়ে গেছে" or "পরে", up to ten times.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->boolean('alarm')->default(false)->after('repeat');
            $table->unsignedSmallInteger('rings')->default(0)->after('alarm');
            $table->dateTime('last_rung_at')->nullable()->after('rings');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['alarm', 'rings', 'last_rung_at']);
        });
    }
};
