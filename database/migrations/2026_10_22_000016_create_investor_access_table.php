<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which projects an investor's login may see, with their share of the profit and the party
 * whose capital entries are theirs. An investor sees nothing else of the farm.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->decimal('share_percent', 5, 2)->nullable();
            $table->date('since')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'cost_center_id']);
        });

        // A farm already running gets the investor role here; a fresh install gets it from the roles migration.
        if (! DB::table('roles')->where('key', 'investor')->exists()) {
            DB::table('roles')->insert([
                'key' => 'investor',
                'name' => 'বিনিয়োগকারী',
                'abilities' => json_encode(['investor-view']),
                'requires_two_factor' => false,
                'is_system' => true,
                'sort' => (int) DB::table('roles')->max('sort') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_access');
    }
};
