<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(1)->create();

        foreach ($users as $user) {
            $user->email_verified_at = now();
            $user->date_archived = null;
            $user->save();
        }

        User::firstOrCreate(
            ['email' => 'lsuizo72@gmail.com'],
            [
                'uuid' => Str::uuid(),
                'firstname' => 'Luis',
                'lastname' => 'Suizo',
                'password' => 'password',
                'email_verified_at' => now(),
                'phone' => '+639566401574',
                'birthday' => '2002-09-14',
                'is_active' => true,
                'date_archived' => null,
            ]
        );
    }
}
