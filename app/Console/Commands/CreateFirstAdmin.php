<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\AdminNavigation;
use App\Constants\Roles;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Production setup: create the first administrator without a seeder or
 * tinker. Roles and permissions are synced first, then the person is invited
 * with a set-password link (no password is generated or shown). They set up
 * two-factor authentication on first sign-in.
 */
class CreateFirstAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-first
        {--name= : The administrator\'s name}
        {--email= : The administrator\'s email address; the set-password link goes here}
        {--force : Invite another administrator even though one exists already}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the first administrator and email them a link to set their password';

    /**
     * Execute the console command.
     */
    public function handle(UserService $users, RoleService $roles): int
    {
        $roles->syncRolesAndPermissions();

        $existing = $users->countActiveAdmins();

        if ($existing > 0 && ! $this->option('force')) {
            $this->components->error(sprintf(
                '%d active administrator%s already exist%s. Invite more from Users & roles in the dashboard, or pass --force.',
                $existing,
                $existing === 1 ? '' : 's',
                $existing === 1 ? 's' : '',
            ));

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?? $this->ask('Name')));
        $email = trim((string) ($this->option('email') ?? $this->ask('Email address')));

        $validator = Validator::make(['name' => $name, 'email' => $email], [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::INVALID;
        }

        $user = $users->invite(['name' => $name, 'email' => $email, 'role' => Roles::ADMIN]);

        $this->components->info(sprintf('%s <%s> has been created as an administrator.', $user->name, $user->email));
        $expires = (int) config('auth.passwords.'.config('fortify.passwords', 'users').'.expire', 60);

        foreach ([
            'A set-password link has been emailed to '.$user->email.' (mailer: '.config('mail.default').').',
            'The link expires after '.$expires.' minutes; an administrator can resend it from Users & roles.',
            'They sign in at '.route('login').' once the password is set.',
            'Two-factor authentication is required before the dashboard opens; they set it up at '.route(AdminNavigation::ACCOUNT_ROUTE).'.',
            'If mail is queued, a queue worker must be running for the email to go out.',
        ] as $note) {
            $this->line('  - '.$note);
        }

        return self::SUCCESS;
    }
}
