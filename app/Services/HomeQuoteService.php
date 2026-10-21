<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Models\HomeQuote;
use App\Repositories\Contracts\HomeQuoteRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The quotations editors choose for the interludes between the home page
 * chapters. Published quotes appear in display order, one per interlude.
 *
 * @extends AbstractContentService<HomeQuote>
 */
class HomeQuoteService extends AbstractContentService
{
    public const int AUDIT_LABEL_LENGTH = 80;

    public function __construct(HomeQuoteRepositoryInterface $quotes, AuditLogService $audit, CacheRepository $cache)
    {
        parent::__construct($quotes, $audit, $cache);
    }

    protected function cacheKey(): string
    {
        return CacheKeys::HOME_QUOTES;
    }

    /**
     * Quotes have no title, so the audit log is labelled with the opening words.
     *
     * @param  HomeQuote  $model
     * @return array<string, mixed>
     */
    protected function auditMeta(Model $model): array
    {
        return ['label' => Str::limit((string) $model->getAttribute('quote'), self::AUDIT_LABEL_LENGTH)];
    }
}
