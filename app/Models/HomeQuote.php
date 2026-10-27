<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableContent;
use App\Models\Concerns\Publishable;
use Database\Factories\HomeQuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A short quotation chosen by editors for the evergreen interludes between the
 * home page chapters: a line from his writing or from a tribute.
 */
#[Fillable(['quote', 'attribution', 'source', 'status', 'published_at', 'sort_order'])]
class HomeQuote extends Model
{
    /** @use HasFactory<HomeQuoteFactory> */
    use HasFactory, HasTranslatableContent, Publishable, SoftDeletes;

    /**
     * Language-specific columns (English and Arabic); the first is the primary one.
     *
     * @var list<string>
     */
    public array $translatable = ['quote', 'attribution'];

    /**
     * "Name, Source", either part optional, or null when neither is set.
     */
    public function credit(): ?string
    {
        $parts = array_filter([$this->attribution, $this->source], fn (?string $part): bool => filled($part));

        return $parts === [] ? null : implode(', ', $parts);
    }
}
