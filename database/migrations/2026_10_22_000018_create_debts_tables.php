<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money that has to come back, both ways: what the farm gave someone (a land deposit, a loan
 * to a person, an advance — it will get it back) and what it took (from an investor or anyone
 * — it has to pay it back, at once or in instalments). Each one keeps its own account, so the
 * books show who holds how much, and none of it mixes with cash in hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            // out: we gave, it comes back. in: we took, we pay it back.
            $table->string('direction', 3);
            $table->foreignId('account_id')->unique()->constrained('accounts');
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('person_name');
            $table->string('phone', 30)->nullable();
            $table->string('purpose')->nullable();
            $table->date('started_on');
            $table->date('due_on')->nullable();
            $table->date('closed_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('debt_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->bigInteger('amount');
            $table->bigInteger('paid')->default(0);
            $table->timestamps();
            $table->index(['due_on']);
        });

        // The land deposit already in the books (account 1700) becomes the first one.
        $deposit = DB::table('accounts')->where('code', '1700')->first();

        if ($deposit) {
            $hasEntries = DB::table('journal_lines')->where('account_id', $deposit->id)->exists();
            DB::table('accounts')->where('id', $deposit->id)->update([
                'subtype' => 'lent',
                'name' => 'ফেরত পাবো: মিঠুর মামাতো ভাই — জমির জামানত',
            ]);

            if ($hasEntries) {
                DB::table('debts')->insert([
                    'direction' => 'out',
                    'account_id' => $deposit->id,
                    'person_name' => 'মিঠুর মামাতো ভাই',
                    'purpose' => 'জমির জামানত / বন্ধক',
                    'started_on' => DB::table('journal_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                        ->where('journal_lines.account_id', $deposit->id)->min('journal_entries.date') ?? now()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_installments');
        Schema::dropIfExists('debts');
    }
};
