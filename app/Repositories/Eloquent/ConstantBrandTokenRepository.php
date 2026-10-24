<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\Brand;
use App\Constants\Components;
use App\Constants\Motion;
use App\Constants\Ornaments;
use App\Constants\Typesetting;
use App\Constants\Typography;
use App\Repositories\Contracts\BrandTokenRepositoryInterface;

/**
 * Brand tokens served from app/Constants. No model is involved because the
 * values are compiled into the stylesheet and must never vary per environment.
 */
final class ConstantBrandTokenRepository implements BrandTokenRepositoryInterface
{
    public function colours(): array
    {
        return Brand::colours();
    }

    public function colourRoles(): array
    {
        return Brand::roles();
    }

    public function functionalColours(): array
    {
        return Brand::functionalColours();
    }

    public function functionalColourRoles(): array
    {
        return Brand::functionalRoles();
    }

    public function fonts(): array
    {
        return Typography::fonts();
    }

    public function typeScale(): array
    {
        return Typography::scale();
    }

    public function motion(): array
    {
        return Motion::behaviours();
    }

    public function typesettingRules(): array
    {
        return [
            'units' => Typesetting::units(),
            'bindingAbbreviations' => Typesetting::bindingAbbreviations(),
            'capsExceptions' => Typesetting::capsExceptions(),
        ];
    }

    public function typesetting(): array
    {
        return [
            'features' => Typesetting::features(),
            'rhythm' => Typesetting::rhythm(),
            'baseline' => Typesetting::BASELINE_PX,
            'bodyLine' => Typesetting::BODY_LINE_PX,
            'measure' => ['min' => Typesetting::MEASURE_MIN_CHARS, 'max' => Typesetting::MEASURE_MAX_CHARS],
        ];
    }

    public function ornaments(): array
    {
        return Ornaments::definitions();
    }

    public function components(): array
    {
        return Components::byGroup();
    }

    public function componentGroups(): array
    {
        return Components::groups();
    }
}
