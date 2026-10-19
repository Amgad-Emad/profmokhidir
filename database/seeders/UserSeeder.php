<?php

namespace Database\Seeders;

use App\Constants\Roles;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Local accounts. Passwords are generated per run and printed to the console;
 * existing accounts are left untouched (their password is not reset).
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(UserService $users, UserRepositoryInterface $repository): void
    {
        $accounts = [
            ['name' => 'Site Administrator', 'email' => 'admin@example.com', 'role' => Roles::ADMIN],
            ['name' => 'Content Editor', 'email' => 'editor@example.com', 'role' => Roles::EDITOR],
        ];

        $rows = [];

        foreach ($accounts as $account) {
            $existing = $repository->findByEmail($account['email']);

            if ($existing !== null) {
                $rows[] = [$account['email'], Roles::labels()[$account['role']], '(existing, password unchanged)'];

                continue;
            }

            $password = Str::random(16);

            $users->create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => $password,
                'role' => $account['role'],
            ]);

            $rows[] = [$account['email'], Roles::labels()[$account['role']], $password];
        }

        $this->seedCommunity($repository, $rows);

        $this->command?->newLine();
        $this->command?->info('Seeded accounts (store these passwords now; they are not shown again):');
        $this->command?->table(['Email', 'Role', 'Password'], $rows);
    }

    /**
     * Two approved members and two pending applicants.
     *
     * @param  list<array{0: string, 1: string, 2: string}>  $rows
     */
    private function seedCommunity(UserRepositoryInterface $repository, array &$rows): void
    {
        $admin = $repository->findByEmail('admin@example.com');

        $members = [
            ['name' => 'Amina Hassan', 'email' => 'member1@example.com'],
            ['name' => 'Yusuf Abdallah', 'email' => 'member2@example.com'],
        ];

        foreach ($members as $member) {
            if ($repository->findByEmail($member['email']) !== null) {
                continue;
            }

            $password = Str::random(16);

            User::factory()->member()->reviewedBy($admin)->create([
                'name' => $member['name'],
                'email' => $member['email'],
                'password' => $password,
            ]);

            $rows[] = [$member['email'], Roles::labels()[Roles::MEMBER], $password];
        }

        $applicants = [
            ['name' => 'Fatima El-Tayeb', 'email' => 'applicant1@example.com'],
            ['name' => 'Omar Siddig', 'email' => 'applicant2@example.com'],
        ];

        foreach ($applicants as $applicant) {
            if ($repository->findByEmail($applicant['email']) !== null) {
                continue;
            }

            $password = Str::random(16);

            User::factory()->applicant()->create([
                'name' => $applicant['name'],
                'email' => $applicant['email'],
                'password' => $password,
            ]);

            $rows[] = [$applicant['email'], Roles::labels()[Roles::APPLICANT].' (pending)', $password];
        }
    }
}
