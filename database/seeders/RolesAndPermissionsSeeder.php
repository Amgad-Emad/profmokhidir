<?php

namespace Database\Seeders;

use App\Services\RoleService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Creates every role and permission from app/Constants and syncs the matrix in
 * Permissions::matrix(). Safe to re-run.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(RoleService $roles): void
    {
        $roles->syncRolesAndPermissions();
    }
}
