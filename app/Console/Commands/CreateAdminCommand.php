<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('khamar:create-admin')]
#[Description('Create the first hishab admin on a fresh server')]
class CreateAdminCommand extends Command
{
    public function handle(): int
    {
        $name = text('নাম', required: true);
        $email = text('ইমেইল', required: true, validate: fn (string $value) => Validator::make(['email' => $value], ['email' => 'email|unique:users,email'])->errors()->first('email') ?: null);
        $secret = password('পাসওয়ার্ড (কমপক্ষে ১২ অক্ষর)', required: true, validate: fn (string $value) => Validator::make(['password' => $value], ['password' => Password::min(12)->mixedCase()->numbers()])->errors()->first('password') ?: null);

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $secret,
            'role' => Role::Admin,
            'is_active' => true,
        ])->forceFill(['email_verified_at' => now()])->save();

        $this->info('অ্যাডমিন তৈরি হয়েছে। প্রথম লগইনের পর ২-ধাপ যাচাই (2FA) চালু করতে হবে।');

        return self::SUCCESS;
    }
}
