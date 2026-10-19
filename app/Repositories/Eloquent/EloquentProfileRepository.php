<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Profile;
use App\Repositories\Contracts\ProfileRepositoryInterface;

/**
 * @extends BaseContentRepository<Profile>
 */
class EloquentProfileRepository extends BaseContentRepository implements ProfileRepositoryInterface
{
    protected array $searchColumns = ['name', 'title', 'affiliation'];

    protected array $sortableColumns = ['name'];

    protected array $publishedWith = ['media'];

    public function __construct(Profile $profile)
    {
        parent::__construct($profile);
    }

    public function current(): ?Profile
    {
        return $this->query()->with('media')->orderBy('id')->first();
    }

    public function currentPublished(): ?Profile
    {
        return $this->query()->published()->with('media')->orderBy('id')->first();
    }

    public function save(array $attributes): Profile
    {
        $profile = $this->current();

        if ($profile === null) {
            return $this->create($attributes);
        }

        return $this->update($profile, $attributes);
    }
}
