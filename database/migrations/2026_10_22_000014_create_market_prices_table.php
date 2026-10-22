<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * বাজারদর: haat and market prices written down by date, so selling paddy now or later,
 * buying straw or using our own, and when to sell fattened cattle rest on real numbers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('commodity', 120);
            $table->string('unit', 10);
            $table->bigInteger('price');
            $table->string('place', 120)->nullable();
            $table->string('quality', 160)->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['commodity', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
