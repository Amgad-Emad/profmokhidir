<?php

namespace Tests\Feature\Requests;

use App\Constants\TributeRelationship;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TributeIndexRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_valid_relationship_filters_the_book(): void
    {
        Tribute::factory()->approved()->relationship(TributeRelationship::Student)->create(['name' => 'Zq Student']);
        Tribute::factory()->approved()->relationship(TributeRelationship::Family)->create(['name' => 'Zq Family']);

        $this->get(route('tributes', ['relationship' => 'family']))
            ->assertOk()
            ->assertSee('Zq Family')
            ->assertDontSee('Zq Student');
    }

    #[Test]
    public function an_unknown_relationship_or_page_is_rejected_back_to_the_book(): void
    {
        $this->from('/')->get(route('tributes', ['relationship' => 'stranger']))
            ->assertRedirect(route('tributes'))
            ->assertSessionHasErrors('relationship');

        $this->from('/')->get(route('tributes', ['page' => 'zero']))
            ->assertRedirect(route('tributes'))
            ->assertSessionHasErrors('page');

        $this->get(route('tributes', ['relationship' => '']))->assertOk();
    }
}
