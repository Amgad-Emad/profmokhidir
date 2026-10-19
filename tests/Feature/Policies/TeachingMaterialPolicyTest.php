<?php

namespace Tests\Feature\Policies;

use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeachingMaterialPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function download_follows_publication_status_and_access_level(): void
    {
        $public = TeachingMaterial::factory()->published()->create();
        $members = TeachingMaterial::factory()->published()->membersOnly()->create();
        $draft = TeachingMaterial::factory()->draft()->create();

        $this->assertTrue(Gate::forUser(null)->allows('download', $public));
        $this->assertFalse(Gate::forUser(null)->allows('download', $members));
        $this->assertFalse(Gate::forUser(null)->allows('download', $draft));

        $applicant = User::factory()->applicant()->create();
        $this->assertTrue(Gate::forUser($applicant)->allows('download', $public));
        $this->assertFalse(Gate::forUser($applicant)->allows('download', $members));

        $member = User::factory()->member()->create();
        $this->assertTrue(Gate::forUser($member)->allows('download', $members));
        $this->assertFalse(Gate::forUser($member)->allows('download', $draft));

        $suspended = User::factory()->suspendedMember()->create();
        $this->assertFalse(Gate::forUser($suspended)->allows('download', $members));

        $editor = User::factory()->editor()->create();
        $this->assertTrue(Gate::forUser($editor)->allows('download', $members));
        $this->assertTrue(Gate::forUser($editor)->allows('download', $draft));
    }
}
