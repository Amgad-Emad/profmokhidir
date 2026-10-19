<?php

namespace App\Models;

use App\Constants\CvSection;
use App\Models\Concerns\Publishable;
use Database\Factories\CvEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'section', 'title', 'organisation', 'role_description', 'start_year', 'end_year',
    'last_updated_at', 'status', 'published_at', 'sort_order',
])]
class CvEntry extends Model
{
    /** @use HasFactory<CvEntryFactory> */
    use HasFactory, Publishable;

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
}
