<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Profile;

/**
 * @extends ContentRepositoryInterface<Profile>
 */
interface ProfileRepositoryInterface extends ContentRepositoryInterface
{
    /**
     * The single profile row, if it exists.
     */
    public function current(): ?Profile;

    /**
     * The single published profile row, with its portrait loaded.
     */
    public function currentPublished(): ?Profile;

    /**
     * Create the row on first save, update it afterwards.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function save(array $attributes): Profile;
}
