<?php

use Database\Seeders\CropSetupSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Land the farm uses (own, leased or shared) and crop seasons on it, so each আমন or বোরো
 * shows its own cost, harvest, profit per bigha and cost per kg of paddy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->decimal('area_decimals', 10, 2);
            $table->string('tenure', 10)->default('lease');
            $table->string('owner_name')->nullable();
            $table->string('owner_phone', 30)->nullable();
            $table->date('lease_from')->nullable();
            $table->date('lease_to')->nullable();
            $table->bigInteger('yearly_rent')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crop_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->string('crop', 60);
            $table->string('name', 120);
            $table->string('variety', 120)->nullable();
            $table->date('started_on');
            $table->date('sown_on')->nullable();
            $table->date('harvested_on')->nullable();
            $table->date('closed_on')->nullable();
            $table->decimal('target_mon_per_bigha', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('crop_season_plot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plot_id')->constrained();
            $table->decimal('area_decimals', 10, 2);
            $table->unique(['crop_season_id', 'plot_id']);
        });

        (new CropSetupSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_season_plot');
        Schema::dropIfExists('crop_seasons');
        Schema::dropIfExists('plots');
    }
};
