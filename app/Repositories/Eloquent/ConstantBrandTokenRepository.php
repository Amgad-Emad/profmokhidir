<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\Brand;
use App\Constants\Motion;
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
}
