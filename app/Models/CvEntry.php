<?php

namespace App\Models;

use App\Constants\CvSection;
use App\Constants\DateFormats;
use App\Models\Concerns\Publishable;
use Database\Factories\CvEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'section', 'title', 'organisation', 'role_description', 'start_year', 'end_year',
    'last_updated_at', 'status', 'published_at', 'sort_order',
])]
class CvEntry extends Model
{
    /** @use HasFactory<CvEntryFactory> */
    use HasFactory, Publishable, SoftDeletes;

    protected string $orderDateColumn = 'start_year';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => CvSection::class,
            'start_year' => 'integer',
            'end_year' => 'integer',
            'last_updated_at' => 'date',
        ];
    }

    /**
     * @param  Builder<CvEntry>  $query
     */
    #[Scope]
    protected function inSection(Builder $query, CvSection $section): void
    {
        $query->where('section', $section->value);
    }

    public function isCurrent(): bool
    {
        return $this->end_year === null;
    }

    /**
     * "1969 – 1975", "1990 – present", "1980", or null when no year is set.
     */
    public function yearRange(): ?string
    {
        if ($this->start_year === null && $this->end_year === null) {
            return null;
        }

        if ($this->start_year === null) {
            return (string) $this->end_year;
        }

        if ($this->end_year === null) {
            return $this->start_year.DateFormats::RANGE_SEPARATOR.DateFormats::PRESENT;
        }

        if ($this->end_year === $this->start_year) {
            return (string) $this->start_year;
        }

        return $this->start_year.DateFormats::RANGE_SEPARATOR.$this->end_year;
    }
}
