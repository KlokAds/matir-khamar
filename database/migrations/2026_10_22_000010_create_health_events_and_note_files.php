<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vaccines, deworming and treatments — given to one animal or a whole category — with the
 * day the next one is due; and photos or voice notes kept with diary pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->nullable()->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->string('kind', 12);
            $table->string('name');
            $table->date('given_on');
            $table->date('next_due_on')->nullable();
            $table->string('dose')->nullable();
            $table->string('by_whom')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->index(['next_due_on']);
        });

        Schema::create('farm_note_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_note_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_note_files');
        Schema::dropIfExists('health_events');
    }
};
