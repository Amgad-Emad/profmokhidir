<?php

namespace Database\Seeders;

use App\Constants\RevisionKind;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\RevisionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Seeder;

/**
 * A first version of the profile in its history, so the version history panel
 * has something to show locally. Real saves add their own rows.
 */
class RevisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(ProfileRepositoryInterface $profiles, RevisionRepositoryInterface $revisions, UserRepositoryInterface $users): void
    {
        $profile = $profiles->all()->first();

        if ($profile === null || $revisions->countFor($profile) > 0) {
            return;
        }

        $revisions->record($profile, $users->findByEmail('editor@example.com')?->getKey(), RevisionKind::Save, [
            'name' => $profile->name,
            'title' => $profile->title,
            'affiliation' => $profile->affiliation,
            'short_bio' => $profile->short_bio,
            'full_bio' => $profile->full_bio,
            'expertise' => $profile->expertise ?? [],
            'key_facts' => $profile->key_facts ?? [],
            'students_supervised' => $profile->students_supervised,
            'contact_email_public' => $profile->contact_email_public,
            'contact_note' => $profile->contact_note,
            'status' => $profile->status->value,
        ]);
    }
}
