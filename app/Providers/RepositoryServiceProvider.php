<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\AwardRepositoryInterface;
use App\Repositories\Contracts\BrandTokenRepositoryInterface;
use App\Repositories\Contracts\ChatChannelRepositoryInterface;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Contracts\ChatReadRepositoryInterface;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use App\Repositories\Contracts\CommentaryRepositoryInterface;
use App\Repositories\Contracts\CvEntryRepositoryInterface;
use App\Repositories\Contracts\HomeQuoteRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\OtherItemRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SearchRepositoryInterface;
use App\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Contracts\TeachingMaterialRepositoryInterface;
use App\Repositories\Contracts\TributeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Repositories\Eloquent\ConstantBrandTokenRepository;
use App\Repositories\Eloquent\ConstantSectionRepository;
use App\Repositories\Eloquent\EloquentAlbumRepository;
use App\Repositories\Eloquent\EloquentAuditLogRepository;
use App\Repositories\Eloquent\EloquentAwardRepository;
use App\Repositories\Eloquent\EloquentChatChannelRepository;
use App\Repositories\Eloquent\EloquentChatMessageRepository;
use App\Repositories\Eloquent\EloquentChatReadRepository;
use App\Repositories\Eloquent\EloquentChatReportRepository;
use App\Repositories\Eloquent\EloquentCommentaryRepository;
use App\Repositories\Eloquent\EloquentCvEntryRepository;
use App\Repositories\Eloquent\EloquentHomeQuoteRepository;
use App\Repositories\Eloquent\EloquentOtherItemRepository;
use App\Repositories\Eloquent\EloquentProfileRepository;
use App\Repositories\Eloquent\EloquentPublicationRepository;
use App\Repositories\Eloquent\EloquentSearchRepository;
use App\Repositories\Eloquent\EloquentSettingRepository;
use App\Repositories\Eloquent\EloquentTeachingMaterialRepository;
use App\Repositories\Eloquent\EloquentTributeRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\Eloquent\EloquentVideoRepository;
use App\Repositories\Eloquent\SpatieMediaRepository;
use App\Repositories\Eloquent\SpatieRoleRepository;
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
        SectionRepositoryInterface::class => ConstantSectionRepository::class,
        RoleRepositoryInterface::class => SpatieRoleRepository::class,
        MediaRepositoryInterface::class => SpatieMediaRepository::class,
        ProfileRepositoryInterface::class => EloquentProfileRepository::class,
        CvEntryRepositoryInterface::class => EloquentCvEntryRepository::class,
        PublicationRepositoryInterface::class => EloquentPublicationRepository::class,
        AwardRepositoryInterface::class => EloquentAwardRepository::class,
        VideoRepositoryInterface::class => EloquentVideoRepository::class,
        AlbumRepositoryInterface::class => EloquentAlbumRepository::class,
        TeachingMaterialRepositoryInterface::class => EloquentTeachingMaterialRepository::class,
        CommentaryRepositoryInterface::class => EloquentCommentaryRepository::class,
        OtherItemRepositoryInterface::class => EloquentOtherItemRepository::class,
        HomeQuoteRepositoryInterface::class => EloquentHomeQuoteRepository::class,
        TributeRepositoryInterface::class => EloquentTributeRepository::class,
        ChatChannelRepositoryInterface::class => EloquentChatChannelRepository::class,
        ChatMessageRepositoryInterface::class => EloquentChatMessageRepository::class,
        ChatReadRepositoryInterface::class => EloquentChatReadRepository::class,
        ChatReportRepositoryInterface::class => EloquentChatReportRepository::class,
        SearchRepositoryInterface::class => EloquentSearchRepository::class,
        AuditLogRepositoryInterface::class => EloquentAuditLogRepository::class,
        SettingRepositoryInterface::class => EloquentSettingRepository::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
