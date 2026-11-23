<?php

namespace Tests;

use App\Constants\PageDefinitions\HomeBlocks;
use App\Constants\Pages;
use App\Services\HomeSectionsImportService;
use App\Services\PageService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Views are tested without a compiled Vite manifest.
        $this->withoutVite();
    }

    /**
     * Fill the home page's chapters, figures and cards (Admin › Pages › Home) from the records a
     * test created, as the migration did when those sections moved into the page editor. They are
     * emptied first: the migration already filled them when the test database was built.
     */
    protected function fillHomeSections(): void
    {
        $pages = app(PageService::class);
        $pages->install();
        $pages->publishBlocks($pages->findByKey(Pages::HOME), [
            HomeBlocks::CHAPTER_ITEMS => [],
            HomeBlocks::FIGURES => [],
            HomeBlocks::STORY_CARDS => [],
        ]);
        app(HomeSectionsImportService::class)->import();
    }
}
