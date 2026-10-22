<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The labour register (মজুরির খাতা): the people who work on the farm, and each day's work —
 * who, on which project, how many days, at what rate — until it is paid in one entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('phone', 30)->nullable();
            $table->string('kind', 10)->default('daily');
            $table->bigInteger('daily_rate')->nullable();
            $table->bigInteger('monthly_salary')->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('work_logs', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('worker_id')->constrained('workers');
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->foreignId('crop_season_id')->nullable()->constrained('crop_seasons')->nullOnDelete();
            $table->string('work_type', 60);
            $table->decimal('days', 5, 2);
            $table->bigInteger('rate');
            $table->bigInteger('amount');
            $table->text('notes')->nullable();
            $table->foreignId('paid_entry_id')->nullable()->constrained('journal_entries');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['worker_id', 'paid_entry_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_logs');
        Schema::dropIfExists('workers');
    }
};
