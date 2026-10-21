<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rows pulled from billing.klokads.com (Crater). Each source row is stored
 * once with its raw data and a checksum, so it can never be posted twice and
 * a later change on the billing side is spotted instead of silently copied.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('kind', 30);
            $table->string('external_id', 64);
            $table->string('external_name');
            $table->foreignId('account_id')->nullable()->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->foreignId('item_id')->nullable()->constrained();
            $table->boolean('ignore')->default(false);
            $table->timestamps();
            $table->unique(['source', 'kind', 'external_id']);
        });

        Schema::create('imported_records', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('external_table', 40);
            $table->string('external_id', 64);
            $table->string('external_number', 60)->nullable();
            $table->date('external_date');
            $table->bigInteger('amount');
            $table->decimal('qty', 14, 3)->nullable();
            $table->string('checksum', 64);
            $table->json('raw');
            $table->string('status', 12)->default('pending');
            $table->string('problem')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_table', 'external_id']);
            $table->index(['status', 'external_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_records');
        Schema::dropIfExists('import_mappings');
    }
};
