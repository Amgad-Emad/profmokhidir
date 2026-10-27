<?php

declare(strict_types=1);

namespace App\Constants;

use App\Constants\Concerns\HasValues;

enum CvSection: string
{
    use HasValues;

    case Education = 'education';
    case Appointment = 'appointment';
    case Leadership = 'leadership';
    case Project = 'project';
    case Achievement = 'achievement';

    public function label(): string
    {
        return __(match ($this) {
            self::Education => 'Education',
            self::Appointment => 'Appointments',
            self::Leadership => 'Leadership',
            self::Project => 'Projects',
            self::Achievement => 'Achievements',
        });
    }
}
