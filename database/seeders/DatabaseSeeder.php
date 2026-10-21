<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * On the live server only the farm setup is seeded; the first admin is
     * created with `php artisan khamar:create-admin`.
     */
    public function run(): void
    {
        $this->call(KhamarSetupSeeder::class);

        if (! app()->environment('local', 'testing')) {
            return;
        }

        // Local development logins. All use the factory password "password".
        foreach ([
            ['হিসাব অ্যাডমিন', 'admin@khamar.test', Role::Admin],
            ['মিঠু রহমান', 'manager@khamar.test', Role::Manager],
            ['দৈনিক এন্ট্রি', 'entry@khamar.test', Role::Entry],
            ['বিনিয়োগকারী', 'viewer@khamar.test', Role::Viewer],
        ] as [$name, $email, $role]) {
            User::query()->where('email', $email)->exists()
                || User::factory()->create(['name' => $name, 'email' => $email, 'role' => $role]);
        }
    }
}
