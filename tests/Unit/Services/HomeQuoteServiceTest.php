<?php

namespace Tests\Unit\Services;

use App\Constants\AuditAction;
use App\Constants\CacheKeys;
use App\Constants\ContentStatus;
use App\Models\AuditLog;
use App\Models\HomeQuote;
use App\Models\User;
use App\Services\HomeQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeQuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    private HomeQuoteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(HomeQuoteService::class);
    }

    #[Test]
    public function published_quotes_are_cached_in_display_order_and_drafts_are_left_out(): void
    {
        HomeQuote::factory()->published()->sortOrder(2)->create(['quote' => 'Second']);
        HomeQuote::factory()->published()->sortOrder(1)->create(['quote' => 'First']);
        HomeQuote::factory()->draft()->sortOrder(0)->create(['quote' => 'Draft']);

        $this->assertSame(['First', 'Second'], $this->service->published()->pluck('quote')->all());
        $this->assertTrue(Cache::has(CacheKeys::HOME_QUOTES));
    }

    #[Test]
    public function saving_reordering_and_publishing_clear_the_cached_list(): void
    {
        $quote = HomeQuote::factory()->draft()->create(['quote' => 'Knowledge is the light we pass on.']);
        $this->service->published();

        $this->service->publish($quote, User::factory()->editor()->create());

        $this->assertFalse(Cache::has(CacheKeys::HOME_QUOTES));
        $this->assertSame(['Knowledge is the light we pass on.'], $this->service->published()->pluck('quote')->all());

        $other = $this->service->create(['quote' => 'Another line.', 'status' => ContentStatus::Published->value]);
        $this->service->reorder([$other->id, $quote->id]);

        $this->assertSame(['Another line.', 'Knowledge is the light we pass on.'], $this->service->published()->pluck('quote')->all());
    }

    #[Test]
    public function the_audit_log_is_labelled_with_the_opening_words_of_the_quote(): void
    {
        $editor = User::factory()->editor()->create();

        $quote = $this->service->create(['quote' => str_repeat('word ', 40)], $editor);

        $log = AuditLog::query()->where('action', AuditAction::CONTENT_CREATED)->sole();
        $this->assertTrue($log->subject->is($quote));
        $this->assertLessThanOrEqual(HomeQuoteService::AUDIT_LABEL_LENGTH + 3, mb_strlen($log->meta['label']));
    }

    #[Test]
    public function the_credit_joins_attribution_and_source(): void
    {
        $this->assertSame('A student, Tribute', HomeQuote::factory()->make(['attribution' => 'A student', 'source' => 'Tribute'])->credit());
        $this->assertSame('Lecture notes', HomeQuote::factory()->make(['attribution' => null, 'source' => 'Lecture notes'])->credit());
        $this->assertNull(HomeQuote::factory()->make(['attribution' => '', 'source' => null])->credit());
    }
}
