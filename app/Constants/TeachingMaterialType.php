<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

enum TeachingMaterialType: string
{
    use HasValues;

    case Slides = 'slides';
    case Notes = 'notes';
    case Reading = 'reading';
    case Exercise = 'exercise';
    case Syllabus = 'syllabus';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Slides => 'Slides',
            self::Notes => 'Lecture notes',
            self::Reading => 'Reading',
            self::Exercise => 'Exercise',
            self::Syllabus => 'Syllabus',
            self::Other => 'Other',
        };
    }
}
