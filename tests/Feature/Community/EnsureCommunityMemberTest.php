<?php

namespace Tests\Feature\Community;

use App\Http\Middleware\EnsureCommunityMember;
use App\Models\User;
use App\Services\CommunityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureCommunityMemberTest extends TestCase
{
    use RefreshDatabase;

    private function handle(?User $user, array $headers = []): mixed
    {
        $request = Request::create('/community/chat/general', 'GET', [], [], [], $headers);
        $request->setUserResolver(fn () => $user);

        return app(EnsureCommunityMember::class)->handle($request, fn () => response('passed'));
    }

    #[Test]
    public function the_alias_is_registered(): void
    {
        $this->assertSame('community.member', EnsureCommunityMember::ALIAS);
        $this->assertSame(EnsureCommunityMember::class, app('router')->getMiddleware()[EnsureCommunityMember::ALIAS] ?? null);
    }

    #[Test]
    public function guests_are_sent_to_sign_in(): void
    {
        $this->assertSame(route('login'), $this->handle(null)->getTargetUrl());
    }

    #[Test]
    public function applicants_rejected_and_suspended_users_are_sent_to_the_community_page(): void
    {
        foreach ([User::factory()->applicant(), User::factory()->rejectedApplicant(), User::factory()->suspendedMember(), User::factory(), User::factory()->editor()] as $factory) {
            $this->assertSame(route('community'), $this->handle($factory->create())->getTargetUrl());
        }
    }

    #[Test]
    public function livewire_requests_from_users_without_access_get_a_plain_403(): void
    {
        try {
            $this->handle(User::factory()->applicant()->create(), ['HTTP_X_LIVEWIRE' => 'true']);
            $this->fail('Expected a 403.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    #[Test]
    public function approved_members_and_admins_pass(): void
    {
        $this->assertSame('passed', $this->handle(User::factory()->member()->create())->getContent());
        $this->assertSame('passed', $this->handle(User::factory()->admin()->create())->getContent());
    }

    #[Test]
    public function a_member_suspended_since_their_last_request_is_refused_on_the_next_one(): void
    {
        $member = User::factory()->member()->create();
        $this->assertSame('passed', $this->handle($member)->getContent());

        app(CommunityService::class)->suspend($member, User::factory()->admin()->create());

        $this->assertSame(route('community'), $this->handle($member)->getTargetUrl());
    }
}
