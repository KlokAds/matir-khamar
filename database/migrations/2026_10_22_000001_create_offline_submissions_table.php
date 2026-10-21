<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every entry made on a phone without internet carries an id made on the
 * phone. The server keeps the outcome per id, so a phone that sends the same
 * entry twice (patchy network, app reopened) gets the first answer back and
 * nothing is booked twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained();
            $table->string('kind', 20);
            $table->json('payload');
            $table->string('status', 10);
            $table->string('message')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_submissions');
    }
};
