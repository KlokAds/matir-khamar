<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Looking after the herd, beyond the money: fattening batches with their day count, weights,
 * services and pregnancies with the expected calving day, and the farm diary with its goals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->unsignedInteger('number');
            $table->string('name')->nullable();
            $table->date('started_on');
            $table->unsignedSmallInteger('target_days')->default(120);
            $table->decimal('target_gain_kg', 6, 2)->nullable();
            $table->date('closed_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['cost_center_id', 'number']);
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('cost_center_id')->constrained('batches');
        });

        Schema::create('animal_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained();
            $table->date('date');
            $table->decimal('kg', 7, 2);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['animal_id', 'date']);
        });

        Schema::create('breedings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained();
            $table->date('served_on');
            $table->boolean('served_on_estimated')->default(false);
            $table->string('method', 10)->default('ai');
            $table->string('sire')->nullable();
            $table->string('by_whom')->nullable();
            $table->string('result', 10)->default('pending');
            $table->date('checked_on')->nullable();
            $table->date('expected_on');
            $table->date('calved_on')->nullable();
            $table->string('outcome', 12)->nullable();
            $table->foreignId('calf_id')->nullable()->constrained('animals');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['animal_id', 'served_on']);
        });

        Schema::create('farm_notes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('kind', 12)->default('daily');
            $table->string('title');
            $table->text('body')->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->foreignId('animal_id')->nullable()->constrained();
            $table->date('follow_up_on')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['date', 'kind']);
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('unit', 30)->nullable();
            $table->decimal('start_value', 14, 2)->default(0);
            $table->decimal('target_value', 14, 2);
            $table->decimal('current_value', 14, 2)->default(0);
            $table->date('due_on')->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->timestamp('achieved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
        Schema::dropIfExists('farm_notes');
        Schema::dropIfExists('breedings');
        Schema::dropIfExists('animal_weights');
        Schema::table('animals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });
        Schema::dropIfExists('batches');
    }
};
