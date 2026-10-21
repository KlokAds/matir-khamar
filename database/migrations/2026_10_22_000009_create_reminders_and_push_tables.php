<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reminders the farm sets for itself ("জমিতে সার দিতে হবে — কাল সকাল ৭টা") and the phones
 * that have agreed to receive them as notifications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique();
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->string('device')->nullable();
            $table->unsignedSmallInteger('failures')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users');
            // Null: everyone who does the daily work.
            $table->foreignId('for_user_id')->nullable()->constrained('users');
            $table->string('title');
            $table->text('body')->nullable();
            $table->dateTime('due_at');
            $table->string('repeat', 10)->default('none');
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->foreignId('animal_id')->nullable()->constrained();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['due_at', 'sent_at', 'done_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('notify_prefs')->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notify_prefs');
        });
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('push_subscriptions');
    }
};
