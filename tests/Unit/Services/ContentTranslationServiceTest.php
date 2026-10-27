<?php

namespace Tests\Unit\Services;

use App\Constants\Locales;
use App\Constants\Placeholders;
use App\Models\Award;
use App\Models\Profile;
use App\Services\ContentTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentTranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function arabic_placeholders_are_marked_and_fill_only_what_is_missing(): void
    {
        $award = Award::factory()->create(['name' => 'Order of Merit', 'organisation' => ['en' => 'University', 'ar' => 'الجامعة'], 'description' => null]);
        $profile = Profile::factory()->create(['key_facts' => [['label' => 'Field', 'value' => 'Agronomy']]]);
        $service = $this->app->make(ContentTranslationService::class);

        $this->assertGreaterThan(0, $service->countNeedingTranslation());
        $service->seedArabicPlaceholders();

        $award->refresh();
        $this->assertStringStartsWith(Placeholders::MARKER_AR, $award->getTranslation('name', Locales::AR, false));
        $this->assertSame('الجامعة', $award->getTranslation('organisation', Locales::AR, false));
        $this->assertNull($award->getTranslation('description', Locales::AR, false));
        $facts = $profile->fresh()->getTranslation('key_facts', Locales::AR, false);
        $this->assertStringStartsWith(Placeholders::MARKER_AR, $facts[0]['label']);
        $this->assertTrue(Placeholders::contains($facts));
        $this->assertSame(0, $service->countNeedingTranslation());
    }

    #[Test]
    public function rich_text_placeholders_stay_rich_text(): void
    {
        $this->assertSame('<p>'.Placeholders::MARKER_AR.' نص المقالة بالعربية، يُستبدل بالنص المعتمد قبل الإطلاق.</p>', $this->app->make(ContentTranslationService::class)->placeholder('body', '<p>Body</p>'));
    }
}
