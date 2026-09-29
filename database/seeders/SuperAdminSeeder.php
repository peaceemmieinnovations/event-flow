<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    public const OWNER_EMAIL = 'peaceemmieinnovations@gmail.com';

    public function run(): void
    {
        $owner = User::firstOrNew(['email' => self::OWNER_EMAIL]);

        if (! $owner->exists) {
            $owner->name = 'EventFlow Owner';
            $owner->password = Hash::make(Str::password(32));
        }

        $owner->role = User::ROLE_SUPER_ADMIN;
        $owner->save();
    }
}
