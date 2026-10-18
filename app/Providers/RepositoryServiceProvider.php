<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\BrandTokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\ConstantBrandTokenRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository interface => implementation.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
        BrandTokenRepositoryInterface::class => ConstantBrandTokenRepository::class,
    ];
}
