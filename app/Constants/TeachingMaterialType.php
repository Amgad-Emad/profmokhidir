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
            self::Slides => __('Slides'),
            self::Notes => __('Lecture notes'),
            self::Reading => __('Reading'),
            self::Exercise => __('Exercise'),
            self::Syllabus => __('Syllabus'),
            self::Other => __('Other'),
        };
    }
}
