<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the kilos went, in farm terms: how much land (in decimals / শতাংশ) the grass was cut
 * from or the fertiliser was spread on, and how many animals stood in a category the day it
 * was fed, so a report can say "40 kg for 5 cows" rather than only "40 kg".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('land_decimals', 10, 2)->nullable()->after('qty');
            $table->unsignedInteger('head_count')->nullable()->after('land_decimals');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['land_decimals', 'head_count']);
        });
    }
};
