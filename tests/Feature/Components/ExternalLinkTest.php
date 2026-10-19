<?php

namespace Tests\Feature\Components;

use App\View\Components\ExternalLink;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExternalLinkTest extends TestCase
{
    #[Test]
    public function it_names_the_destination_host_and_opens_in_the_same_tab(): void
    {
        $html = Blade::render('<x-external-link href="https://www.Example.org/path?q=1" label="Source" class="mt-2" />');

        $this->assertStringContainsString('<a href="https://www.Example.org/path?q=1" rel="noopener" class="link-meta mt-2">Source · example.org</a>', $html);
        $this->assertStringNotContainsString('target=', $html);
    }

    #[Test]
    public function it_defaults_the_label_and_copes_with_an_unparseable_url(): void
    {
        $this->assertStringContainsString('>Visit · library.example.edu<', Blade::render('<x-external-link href="https://library.example.edu/list" />'));
        $this->assertStringContainsString('>Visit<', Blade::render('<x-external-link href="not a url" />'));
        $this->assertSame('', ExternalLink::hostOf('/relative/path'));
        $this->assertSame('doi.org', ExternalLink::hostOf('https://doi.org/10.1/x'));
    }
}
