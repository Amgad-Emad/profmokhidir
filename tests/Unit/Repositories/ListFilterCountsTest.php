<?php

namespace Tests\Unit\Repositories;

use App\Constants\AccountState;
use App\Constants\CommunityStatus;
use App\Constants\ContentStatus;
use App\Constants\Disks;
use App\Constants\MediaCollections;
use App\Constants\MediaProperties;
use App\Constants\PublicationType;
use App\Constants\Roles;
use App\Models\Album;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\Publication;
use App\Models\User;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use App\Repositories\Contracts\MediaRepositoryInterface;
use App\Repositories\Contracts\PublicationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AlbumService;
use App\Support\PlaceholderImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The queries behind the list toolbar's counts and multi-choice filters:
 * content (count, counts per value, lists of values), users, chat reports and images.
 */
class ListFilterCountsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function content_counts_match_search_and_lists_match_any_value(): void
    {
        $publications = $this->app->make(PublicationRepositoryInterface::class);
        // A fixed topic: the factory's random "Soil fertility" would match the search for "soil".
        Publication::factory()->published()->create(['title' => 'Soil survey', 'topic' => 'Agronomy', 'type' => PublicationType::Book, 'year' => 1990]);
        Publication::factory()->draft()->create(['title' => 'Soil chemistry', 'topic' => 'Agronomy', 'type' => PublicationType::Report, 'year' => 1990]);
        Publication::factory()->published()->create(['title' => 'Wheat yields', 'topic' => 'Agronomy', 'type' => PublicationType::JournalArticle, 'year' => 2001]);
        Publication::factory()->published()->create(['title' => 'Old soil notes', 'topic' => 'Agronomy', 'type' => PublicationType::Book, 'year' => 1975])->delete();

        $this->assertSame(3, $publications->count());
        $this->assertSame(2, $publications->count('soil'));
        $this->assertSame(2, $publications->count(null, ['type' => ['book', 'report']]));
        $this->assertSame(1, $publications->count('soil', ['type' => ['book', 'report'], 'status' => ContentStatus::Published->value]));
        $this->assertSame(3, $publications->count(null, ['type' => []]));
        $this->assertSame(['Soil chemistry', 'Soil survey'], $publications->search(null, ['year' => ['1990']], 20, 'title')->getCollection()->map(fn ($p) => $p->getTranslation('title', 'en'))->all());

        $this->assertSame(['1990' => 2, '2001' => 1], $this->byKey($publications->countBy('year')));
        // The column's own filter is left out; the others apply.
        $this->assertSame(['draft' => 1, 'published' => 1], $this->byKey($publications->countBy('status', 'soil', ['status' => 'draft'])));
        $this->assertSame(['book' => 1, 'report' => 1], $this->byKey($publications->countBy('type', null, ['year' => ['1990']])));

        $this->expectException(InvalidArgumentException::class);
        $publications->countBy('title');
    }

    #[Test]
    public function users_filter_by_role_account_community_and_two_factor(): void
    {
        $users = $this->app->make(UserRepositoryInterface::class);
        $admin = User::factory()->admin()->withTwoFactor()->create(['name' => 'Amal Admin']);
        $editor = User::factory()->editor()->create(['name' => 'Badr Editor']);
        $invited = User::factory()->editor()->invited()->create(['name' => 'Camil Invited']);
        $gone = User::factory()->editor()->deactivated()->create(['name' => 'Dina Gone']);
        $member = User::factory()->member()->create(['name' => 'Eman Member']);
        $applicant = User::factory()->applicant()->unverified()->create(['name' => 'Fadi Applicant']);

        $names = fn (array $criteria, ?string $term = null, ?string $sort = null, string $direction = 'asc'): array => $users->filter($term, $criteria, 50, $sort, $direction)->getCollection()->pluck('name')->all();

        $this->assertSame(['Amal Admin', 'Badr Editor', 'Camil Invited', 'Dina Gone'], $names([UserRepositoryInterface::FILTER_ROLE => [Roles::ADMIN, Roles::EDITOR]]));
        $this->assertSame(['Camil Invited'], $names([UserRepositoryInterface::FILTER_ACCOUNT => AccountState::Invited->value]));
        $this->assertSame(['Fadi Applicant'], $names([UserRepositoryInterface::FILTER_ACCOUNT => AccountState::Unverified->value]));
        $this->assertSame(['Camil Invited', 'Dina Gone'], $names([UserRepositoryInterface::FILTER_ACCOUNT => [AccountState::Invited->value, AccountState::Deactivated->value]]));
        $this->assertSame(['Eman Member'], $names([UserRepositoryInterface::FILTER_COMMUNITY => CommunityStatus::Approved->value]));
        $this->assertSame(['Amal Admin'], $names([UserRepositoryInterface::FILTER_TWO_FACTOR => UserRepositoryInterface::YES]));
        $this->assertSame(['Fadi Applicant'], $names([UserRepositoryInterface::FILTER_EMAIL => UserRepositoryInterface::NO, UserRepositoryInterface::FILTER_COMMUNITY => [CommunityStatus::Pending->value]]));
        $this->assertSame(['Badr Editor'], $names([], 'badr'));
        $this->assertSame('Fadi Applicant', $names([], null, 'name', 'desc')[0]);
        $this->assertSame(6, $users->countFiltered(null, []));
        $this->assertSame(1, $users->countFiltered('editor', [UserRepositoryInterface::FILTER_ACCOUNT => AccountState::Active->value]));
        $this->assertSame(0, $users->countFiltered(null, [UserRepositoryInterface::FILTER_ROLE => 'nobody']));
        $this->assertNotNull($admin->two_factor_confirmed_at);
        $this->assertTrue($editor->isActive() && $member->isActive() && ! $gone->isActive() && $invited->isActive() && $applicant->isActive());
    }

    #[Test]
    public function chat_reports_filter_by_state_channel_and_message(): void
    {
        $reports = $this->app->make(ChatReportRepositoryInterface::class);
        $general = ChatChannel::factory()->create(['name' => 'General']);
        $research = ChatChannel::factory()->create(['name' => 'Research']);
        $open = ChatReport::factory()->open()->create(['reason' => 'Rude remark', 'message_id' => ChatMessage::factory()->create(['channel_id' => $general->getKey(), 'body' => 'You are wrong'])]);
        $removed = ChatReport::factory()->open()->create(['reason' => 'Spam', 'message_id' => ChatMessage::factory()->deleted()->create(['channel_id' => $research->getKey()])]);
        $resolved = ChatReport::factory()->resolved()->create(['reason' => 'Off topic', 'message_id' => ChatMessage::factory()->create(['channel_id' => $research->getKey()])]);

        $ids = fn (array $criteria, ?string $term = null, bool $oldest = false): array => $reports->filter($term, $criteria, 20, $oldest)->getCollection()->modelKeys();

        $this->assertSame([$resolved->getKey(), $removed->getKey(), $open->getKey()], $ids([]));
        $this->assertSame([$open->getKey(), $removed->getKey(), $resolved->getKey()], $ids([], null, true));
        $this->assertSame([$removed->getKey(), $open->getKey()], $ids([ChatReportRepositoryInterface::FILTER_STATE => ChatReportRepositoryInterface::STATE_OPEN]));
        $this->assertSame([$resolved->getKey()], $ids([ChatReportRepositoryInterface::FILTER_STATE => ChatReportRepositoryInterface::STATE_RESOLVED]));
        $this->assertSame([$resolved->getKey(), $removed->getKey()], $ids([ChatReportRepositoryInterface::FILTER_CHANNEL => [(string) $research->getKey()]]));
        $this->assertSame([$removed->getKey()], $ids([ChatReportRepositoryInterface::FILTER_MESSAGE => ChatReportRepositoryInterface::MESSAGE_REMOVED]));
        $this->assertSame([$resolved->getKey(), $open->getKey()], $ids([ChatReportRepositoryInterface::FILTER_MESSAGE => ChatReportRepositoryInterface::MESSAGE_VISIBLE]));
        $this->assertSame([$open->getKey()], $ids([], 'wrong'));
        $this->assertSame([$removed->getKey()], $ids([], 'spam'));
        $this->assertSame(1, $reports->countFiltered(null, [ChatReportRepositoryInterface::FILTER_STATE => ChatReportRepositoryInterface::STATE_OPEN, ChatReportRepositoryInterface::FILTER_CHANNEL => [(string) $general->getKey()]]));
    }

    #[Test]
    public function images_are_counted_and_sorted_like_the_library_shows_them(): void
    {
        Storage::fake(Disks::PUBLIC);
        $media = $this->app->make(MediaRepositoryInterface::class);
        $album = Album::factory()->create();
        $first = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(20, 20), [MediaProperties::ALT_TEXT => 'A field']);
        $second = $this->app->make(AlbumService::class)->addPhoto($album, PlaceholderImage::grey(20, 20));
        $first->forceFill(['file_name' => 'b-field.jpg', 'created_at' => now()->subDay()])->save();
        $second->forceFill(['file_name' => 'a-river.jpg'])->save();

        $this->assertSame(2, $media->countImages());
        $this->assertSame(1, $media->countImages(null, [MediaRepositoryInterface::FILTER_MISSING_ALT => true]));
        $this->assertSame(2, $media->countImages(null, [MediaRepositoryInterface::FILTER_COLLECTION => [MediaCollections::PHOTOS, MediaCollections::PORTRAIT]]));
        $this->assertSame(0, $media->countImages(null, [MediaRepositoryInterface::FILTER_COLLECTION => [MediaCollections::PORTRAIT]]));
        $this->assertSame([$second->getKey(), $first->getKey()], $media->searchImages()->getCollection()->modelKeys());
        $this->assertSame([$first->getKey(), $second->getKey()], $media->searchImages(null, [], 24, 'created_at', 'asc')->getCollection()->modelKeys());
        $this->assertSame([$second->getKey(), $first->getKey()], $media->searchImages(null, [], 24, 'file_name', 'asc')->getCollection()->modelKeys());
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    private function byKey(array $counts): array
    {
        ksort($counts);

        return $counts;
    }
}
