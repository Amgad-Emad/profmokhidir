<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Local accounts for each role. Password for all: "password".
     */
    public function run(): void
    {
        $accounts = [
            ['factory' => User::factory()->admin(), 'name' => 'Site Administrator', 'email' => 'admin@profmokhidir.test'],
            ['factory' => User::factory()->editor(), 'name' => 'Content Editor', 'email' => 'editor@profmokhidir.test'],
            ['factory' => User::factory()->member(), 'name' => 'Community Member', 'email' => 'member@profmokhidir.test'],
        ];

        foreach ($accounts as $account) {
            if (User::query()->where('email', $account['email'])->exists()) {
                continue;
            }

            $account['factory']->create([
                'name' => $account['name'],
                'email' => $account['email'],
            ]);
        }
    }
}
