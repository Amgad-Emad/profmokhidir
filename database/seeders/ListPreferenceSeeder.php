<?php

namespace Database\Seeders;

use App\Constants\AdminNavigation;
use App\Constants\ListDensity;
use App\Constants\ListToolbar;
use App\Repositories\Contracts\ListPreferenceRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Seeder;

/**
 * A remembered list view for the local editor, so opening Publications shows
 * how the last-used view comes back: the drafts, in compact rows.
 */
class ListPreferenceSeeder extends Seeder
{
    public function run(UserRepositoryInterface $users, ListPreferenceRepositoryInterface $preferences): void
    {
        $editor = $users->findByEmail('editor@example.com');

        if ($editor === null || $preferences->find($editor, AdminNavigation::PUBLICATIONS) !== null) {
            return;
        }

        $preferences->save($editor, AdminNavigation::PUBLICATIONS, [
            ListToolbar::PARAM_FILTERS => ['status' => 'draft'],
            ListToolbar::PARAM_TAB => 'drafts',
        ], ListDensity::Compact);
    }
}
