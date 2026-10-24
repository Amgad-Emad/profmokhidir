<?php

namespace App\Models;

use Database\Factories\AnalyticsSaltFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The salt of one day's analytics visitor hashes. Only today's row exists:
 * earlier rows are deleted when the day's salt is made (VisitorHashService).
 */
#[Fillable(['day', 'salt'])]
class AnalyticsSalt extends Model
{
    /** @use HasFactory<AnalyticsSaltFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day' => 'date',
        ];
    }
}
