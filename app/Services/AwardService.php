<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Models\Award;
use App\Repositories\Contracts\AwardRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * @extends AbstractContentService<Award>
 */
class AwardService extends AbstractContentService
{
    public function __construct(AwardRepositoryInterface $awards, AuditLogService $audit, CacheRepository $cache)
    {
        parent::__construct($awards, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::AWARDS;
    }
}
