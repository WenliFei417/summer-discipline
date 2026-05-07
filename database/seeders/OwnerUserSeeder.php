<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('OWNER_EMAIL');
        $password = env('OWNER_PASSWORD');
        $name = env('OWNER_NAME', 'Owner');

        if (! is_string($email) || trim($email) === '' || ! is_string($password) || $password === '') {
            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        User::query()->create([
            'name' => $name,
            'email' => trim($email),
            'password' => $password,
        ]);
    }
}
