<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core ledger for the farm.
 *
 * Money is stored as integer poisha (1 taka = 100 poisha) so totals never
 * drift through float rounding. Every journal line can carry a cost center,
 * an animal and a Udyog budget head, which is what lets one purchase be
 * split across categories and still roll up into one farm-wide account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('entry')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
        });

        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('cost_centers');
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('kind', 20)->default('other');
            $table->boolean('holds_animals')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['type', 'name']);
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('accounts');
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->string('subtype', 30)->nullable();
            $table->foreignId('party_id')->nullable()->constrained('parties');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['type', 'subtype']);
        });

        Schema::create('budget_heads', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('details')->nullable();
            $table->bigInteger('approved_amount')->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 30)->unique();
            $table->string('name')->nullable();
            $table->string('sex', 10);
            $table->string('breed')->nullable();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->foreignId('mother_id')->nullable()->constrained('animals');
            $table->string('status', 10)->default('active');
            $table->string('acquisition', 10);
            $table->date('acquired_on');
            $table->date('birth_date')->nullable();
            $table->bigInteger('purchase_cost')->default(0);
            $table->bigInteger('book_value')->default(0);
            $table->date('disposed_on')->nullable();
            $table->bigInteger('sale_amount')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('animal_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained();
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->timestamps();
            $table->index(['cost_center_id', 'from_date']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->date('date');
            $table->string('type', 30);
            $table->string('description');
            $table->foreignId('party_id')->nullable()->constrained('parties');
            $table->string('reference', 60)->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries');
            $table->foreignId('reversed_by_id')->nullable()->constrained('journal_entries');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['date', 'type']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->foreignId('animal_id')->nullable()->constrained();
            $table->foreignId('budget_head_id')->nullable()->constrained();
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);
            $table->string('memo')->nullable();
            $table->index(['account_id', 'cost_center_id']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 10);
            $table->string('kind', 20);
            $table->foreignId('inventory_account_id')->constrained('accounts');
            $table->foreignId('expense_account_id')->constrained('accounts');
            $table->decimal('qty_on_hand', 14, 3)->default(0);
            $table->bigInteger('value_on_hand')->default(0);
            $table->decimal('reorder_level', 14, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained();
            $table->foreignId('journal_entry_id')->constrained();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers');
            $table->date('date');
            $table->string('direction', 3);
            $table->decimal('qty', 14, 3);
            $table->bigInteger('value');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['item_id', 'date']);
        });

        Schema::create('prepaid_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained();
            $table->foreignId('prepaid_account_id')->constrained('accounts');
            $table->foreignId('expense_account_id')->constrained('accounts');
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->string('description');
            $table->bigInteger('total');
            $table->unsignedSmallInteger('months');
            $table->date('start_month');
            $table->unsignedSmallInteger('amortized_months')->default(0);
            $table->timestamps();
        });

        Schema::create('milk_records', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('session', 10);
            $table->foreignId('cost_center_id')->constrained('cost_centers');
            $table->foreignId('animal_id')->nullable()->constrained();
            $table->decimal('liters', 8, 2);
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->index('date');
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('period_locks', function (Blueprint $table) {
            $table->id();
            $table->date('month')->unique();
            $table->foreignId('locked_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action', 30);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'period_locks', 'attachments', 'milk_records', 'prepaid_schedules',
            'stock_movements', 'items', 'journal_lines', 'journal_entries', 'animal_placements',
            'animals', 'budget_heads', 'accounts', 'parties', 'cost_centers',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'last_login_at', 'last_login_ip']);
        });
    }
};
