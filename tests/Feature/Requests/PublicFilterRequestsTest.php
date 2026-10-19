<?php

namespace Tests\Feature\Requests;

use App\Http\Requests\CommentaryIndexRequest;
use App\Http\Requests\PublicationIndexRequest;
use App\Http\Requests\TeachingMaterialIndexRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicFilterRequestsTest extends TestCase
{
    #[Test]
    public function publication_filters_accept_a_type_and_a_four_digit_year(): void
    {
        $rules = (new PublicationIndexRequest)->rules();

        $this->assertTrue(Validator::make(['type' => 'book', 'year' => '1978'], $rules)->passes());
        $this->assertTrue(Validator::make([], $rules)->passes());
        $this->assertTrue(Validator::make(['type' => '', 'year' => ''], $rules)->passes());
        $this->assertFalse(Validator::make(['year' => 'abc'], $rules)->passes());
        $this->assertFalse(Validator::make(['year' => '78'], $rules)->passes());
        $this->assertFalse(Validator::make(['type' => str_repeat('a', 51)], $rules)->passes());
        $this->assertFalse(Validator::make(['type' => ['book']], $rules)->passes());
    }

    #[Test]
    public function publication_filters_are_normalised(): void
    {
        $request = PublicationIndexRequest::create('/publications?type=book&year=1978', 'GET');
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        $this->assertSame(['type' => 'book', 'year' => 1978], $request->filters());

        $empty = PublicationIndexRequest::create('/publications', 'GET');
        $empty->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $empty->validateResolved();

        $this->assertSame(['type' => null, 'year' => null], $empty->filters());
    }

    #[Test]
    public function teaching_and_commentary_filters_are_bounded_strings(): void
    {
        $teaching = (new TeachingMaterialIndexRequest)->rules();
        $this->assertTrue(Validator::make(['topic' => 'Photosynthesis', 'type' => 'slides'], $teaching)->passes());
        $this->assertFalse(Validator::make(['topic' => str_repeat('a', 256)], $teaching)->passes());
        $this->assertFalse(Validator::make(['type' => ['slides']], $teaching)->passes());

        $commentary = (new CommentaryIndexRequest)->rules();
        $this->assertTrue(Validator::make(['tag' => 'policy'], $commentary)->passes());
        $this->assertFalse(Validator::make(['tag' => str_repeat('a', 101)], $commentary)->passes());

        $request = TeachingMaterialIndexRequest::create('/teaching?topic=%20Irrigation%20&type=notes', 'GET');
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $request->validateResolved();
        $this->assertSame(['topic' => 'Irrigation', 'type' => 'notes'], $request->filters());

        $tagged = CommentaryIndexRequest::create('/commentary?tag=%20policy%20', 'GET');
        $tagged->setContainer($this->app)->setRedirector($this->app->make('redirect'));
        $tagged->validateResolved();
        $this->assertSame('policy', $tagged->tag());
    }
}
