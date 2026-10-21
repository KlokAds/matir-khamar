<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles move from code into the database so the owner can tick what each role may do and add
 * new roles (an accountant, a vet...). The four built-in roles start with the abilities they
 * had in code; users keep their role key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('name', 80);
            $table->json('abilities');
            $table->boolean('requires_two_factor')->default(false);
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        foreach (Role::cases() as $index => $role) {
            DB::table('roles')->insert([
                'key' => $role->value,
                'name' => $role->label(),
                'abilities' => json_encode($role->abilities()),
                'requires_two_factor' => $role->requiresTwoFactor(),
                'is_system' => true,
                'sort' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
