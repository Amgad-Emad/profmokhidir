<?php

namespace Tests\Feature\Policies;

use App\Models\Setting;
use App\Models\User;
use App\Policies\SettingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function only_admins_manage_site_settings(): void
    {
        $this->assertInstanceOf(SettingPolicy::class, Gate::getPolicyFor(Setting::class));

        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $member = User::factory()->member()->create();

        $this->assertTrue($admin->can('viewAny', Setting::class));
        $this->assertTrue($admin->can('update', Setting::class));
        $this->assertFalse($editor->can('viewAny', Setting::class));
        $this->assertFalse($editor->can('update', Setting::class));
        $this->assertFalse($member->can('viewAny', Setting::class));
    }
}
