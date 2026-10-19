<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use Database\Factories\AwardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'organisation', 'year', 'description', 'source_url', 'status', 'published_at', 'sort_order'])]
class Award extends Model
{
    /** @use HasFactory<AwardFactory> */
    use HasFactory, Publishable, SoftDeletes;

    protected string $orderDateColumn = 'year';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['year' => 'integer'];
    }
}
