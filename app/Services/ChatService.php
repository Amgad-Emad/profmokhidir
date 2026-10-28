<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\ChatChange;
use App\Constants\ChatSettings;
use App\Constants\RateLimits;
use App\Events\ChatMessageChanged;
use App\Exceptions\ChatMessageConflictException;
use App\Exceptions\ChatMessageRemovedException;
use App\Exceptions\ChatRateLimitException;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\User;
use App\Repositories\Contracts\ChatChannelRepositoryInterface;
use App\Repositories\Contracts\ChatMessageRepositoryInterface;
use App\Repositories\Contracts\ChatReadRepositoryInterface;
use App\Repositories\Contracts\ChatReportRepositoryInterface;
use App\Support\ChatAccess;
use App\Support\ChatMessageData;
use App\Support\ChatText;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Private community chat. Access is checked here on every call, server side,
 * and every reliability rule lives here rather than in the Livewire component:
 * id-only ordering, keyset cursors, idempotent sends, optimistic locking,
 * monotonic read cursors and per-member rate limiting.
 */
class ChatService
{
    public function __construct(
        private readonly ChatChannelRepositoryInterface $channels,
        private readonly ChatMessageRepositoryInterface $messages,
        private readonly ChatReadRepositoryInterface $reads,
        private readonly ChatReportRepositoryInterface $reports,
        private readonly AuditLogService $audit,
        private readonly CommunityService $community,
        private readonly RateLimiter $limiter,
        private readonly ConnectionInterface $db,
        private readonly TypesetterService $typesetter,
        private readonly ChatCorrespondenceService $correspondence,
        private readonly ChatAttachmentService $attachments,
    ) {}

    // -- Channels -----------------------------------------------------------

    /**
     * @return Collection<int, ChatChannel>
     */
    public function channels(): Collection
    {
        return $this->channels->allOrdered();
    }

    public function findChannel(string $slug): ?ChatChannel
    {
        return $this->channels->findBySlug($slug);
    }

    public function firstChannel(): ?ChatChannel
    {
        return $this->channels->allOrdered()->first();
    }

    /**
     * The one Scientific Community room: the channel with the room's slug, else
     * the oldest channel, else a new room (so /community/chat always opens).
     */
    public function room(): ChatChannel
    {
        return $this->channels->findBySlug(ChatSettings::ROOM_SLUG)
            ?? $this->channels->oldest()
            ?? $this->channels->findOrCreateBySlug(ChatSettings::ROOM_SLUG, [
                'name' => ChatSettings::ROOM_NAME,
                'description' => ChatSettings::ROOM_DESCRIPTION,
            ]);
    }

    public function createChannel(string $name, ?string $description = null): ChatChannel
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($this->channels->slugExists($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $this->channels->create([
            'name' => trim($name),
            'slug' => $slug,
            'description' => filled($description) ? trim((string) $description) : null,
        ]);
    }

    /**
     * Channels with the viewer's unread count on each: [channel, unread].
     *
     * @return list<array{channel: ChatChannel, unread: int}>
     */
    public function channelsWithUnread(User $viewer): array
    {
        $this->guardAccess($viewer);

        $unread = $this->messages->unreadCountsFor((int) $viewer->getKey());

        return $this->channels()->map(fn (ChatChannel $channel): array => [
            'channel' => $channel,
            'unread' => $unread[(int) $channel->getKey()] ?? 0,
        ])->values()->all();
    }

    // -- Messages -----------------------------------------------------------

    /**
     * Send a message. Each insert runs in its own short transaction (retried on
     * a deadlock) and the client uuid makes retries idempotent: a double click, a second Enter, a
     * slow-network retry or a second tab all get the original message back.
     *
     * A reply names the message it answers, which must belong to the same channel.
     *
     * Attachments are files the author staged beforehand ([['id' => uuid, 'alt' => description]]):
     * they are claimed inside the same transaction as the insert, so a retry with the same client
     * uuid returns the original message and never attaches a file twice. A message may be files only.
     *
     * @param  list<array{id: string, alt?: string}>  $attachments
     *
     * @throws AuthorizationException
     * @throws ChatRateLimitException
     */
    public function send(ChatChannel $channel, User $author, string $body, string $clientUuid, ?int $replyToId = null, array $attachments = []): ChatMessage
    {
        $this->guardAccess($author);

        $body = $this->cleanBody($body, allowEmpty: $attachments !== []);

        if (! Str::isUuid($clientUuid)) {
            throw new InvalidArgumentException('client_uuid must be a UUID.');
        }

        $replyTo = $replyToId === null ? null : $this->messages->findWithTrashed($replyToId);

        if ($replyToId !== null && ($replyTo === null || (int) $replyTo->channel_id !== (int) $channel->getKey())) {
            throw new InvalidArgumentException(__('The message you are replying to is not in this channel.'));
        }

        $existing = $this->messages->findByClientUuid((int) $author->getKey(), $clientUuid);

        if ($existing !== null) {
            return $existing;
        }

        $limitKey = RateLimits::chatSendKey((int) $author->getKey());

        if ($this->limiter->tooManyAttempts($limitKey, ChatSettings::SEND_LIMIT_PER_MINUTE)) {
            throw ChatRateLimitException::make($this->limiter->availableIn($limitKey));
        }

        $prepared = $this->attachments->prepare($author, $attachments);

        // The channel row is NOT touched inside this transaction: the insert holds a
        // shared lock on it (foreign key), so two members sending at the same moment
        // would deadlock trying to upgrade it. The pointer moves forward after commit.
        [$message, $created] = $this->db->transaction(function () use ($channel, $author, $body, $clientUuid, $replyTo, $prepared): array {
            [$message, $created] = $this->messages->firstOrCreateByClientUuid((int) $author->getKey(), $clientUuid, [
                'channel_id' => $channel->getKey(),
                'body' => $body,
                'reply_to_id' => $replyTo?->getKey(),
                'version' => 1,
            ]);

            if ($created) {
                $this->attachments->claim($message, $author, $prepared);
                $this->reads->markRead((int) $channel->getKey(), (int) $author->getKey(), (int) $message->getKey());
                ChatMessageChanged::dispatch((int) $channel->getKey(), (int) $message->getKey(), ChatChange::Sent);
            }

            return [$message, $created];
        }, 3);

        if ($created) {
            $this->channels->advanceLastMessage($channel, (int) $message->getKey());
            $this->limiter->hit($limitKey, 60);
        }

        return $message;
    }

    /**
     * Edit own message with optimistic locking: a stale version is rejected
     * and a deleted message cannot be edited.
     *
     * @throws AuthorizationException
     * @throws ChatMessageConflictException
     * @throws ChatMessageRemovedException
     */
    public function edit(ChatMessage $message, User $editor, string $body, int $expectedVersion): ChatMessage
    {
        $this->guardAccess($editor);

        if ((int) $message->user_id !== (int) $editor->getKey()) {
            throw new AuthorizationException(__('Only the author may edit a message.'));
        }

        $current = $this->messages->findWithTrashed((int) $message->getKey());

        if ($current === null || $current->trashed()) {
            throw ChatMessageRemovedException::make();
        }

        if (! $this->messages->updateBodyIfVersion($current, $this->cleanBody($body), $expectedVersion)) {
            throw ChatMessageConflictException::forVersion($expectedVersion);
        }

        ChatMessageChanged::dispatch((int) $current->channel_id, (int) $current->getKey(), ChatChange::Edited);

        return $current;
    }

    /**
     * Soft-delete a message: the author or a moderator. Its attached files
     * are deleted with it. Deleting twice is a no-op. Always audited.
     *
     * @throws AuthorizationException
     */
    public function delete(ChatMessage $message, User $actor): bool
    {
        $isAuthor = (int) $message->user_id === (int) $actor->getKey();

        if (! $isAuthor && ! $this->canModerate($actor)) {
            throw new AuthorizationException(__('You may not delete this message.'));
        }

        if ($isAuthor && ! $this->canModerate($actor)) {
            $this->guardAccess($actor);
        }

        $current = $this->messages->findWithTrashed((int) $message->getKey());

        if ($current === null) {
            return false;
        }

        if ($current->trashed()) {
            return true;
        }

        return $this->db->transaction(function () use ($current, $actor, $isAuthor): bool {
            $channelId = (int) $current->channel_id;
            $deleted = $this->messages->delete($current);
            $this->attachments->purge($current);

            $channel = $this->channels->find($channelId);

            if ($channel !== null && (int) $channel->last_message_id === (int) $current->getKey()) {
                $this->channels->setLastMessage($channel, $this->messages->latestIdInChannel($channelId));
            }

            $this->audit->record($actor, AuditAction::CHAT_MESSAGE_DELETED, $current, [
                'channel_id' => $channelId,
                'by_author' => $isAuthor,
            ]);

            ChatMessageChanged::dispatch($channelId, (int) $current->getKey(), ChatChange::Deleted);

            return $deleted;
        });
    }

    /**
     * The newest page of a channel (or the page before a given id), ascending
     * by id. Deleted messages are included as placeholders.
     *
     * @return Collection<int, ChatMessage>
     */
    public function history(ChatChannel $channel, User $viewer, int $limit = ChatSettings::PAGE_SIZE, ?int $beforeId = null): Collection
    {
        $this->guardAccess($viewer);

        return $this->messages->latestInChannel((int) $channel->getKey(), $limit, $beforeId);
    }

    /**
     * Messages after the viewer's cursor, ascending by id.
     *
     * @return Collection<int, ChatMessage>
     */
    public function since(ChatChannel $channel, User $viewer, int $afterId, int $limit = ChatSettings::SYNC_LIMIT): Collection
    {
        $this->guardAccess($viewer);

        return $this->messages->afterId((int) $channel->getKey(), $afterId, $limit);
    }

    /**
     * Messages the viewer already has (id <= cursor) that were edited or
     * deleted at or after a moment.
     *
     * @return Collection<int, ChatMessage>
     */
    public function changedSince(ChatChannel $channel, User $viewer, DateTimeInterface $since, int $upToId, int $limit = ChatSettings::SYNC_LIMIT): Collection
    {
        $this->guardAccess($viewer);

        return $this->messages->changedSince((int) $channel->getKey(), $since, $upToId, $limit);
    }

    public function findMessage(int $id): ?ChatMessage
    {
        return $this->messages->findWithTrashed($id);
    }

    // -- Payloads for the browser -------------------------------------------

    /**
     * Everything the chat page needs on first render.
     *
     * @return array{
     *     messages: list<array<string, mixed>>, cursor: int, since: string, lastRead: int,
     *     hasOlder: bool, channels: list<array{id: int, name: string, slug: string, numeral: string, description: ?string, unread: int, current: bool}>,
     *     focus: ?int, pins: list<array<string, mixed>>, members: list<array{id: int, name: string, affiliation: ?string, initials: string}>,
     *     online: list<int>
     * }
     */
    public function initialState(ChatChannel $channel, User $viewer, ?int $focusId = null): array
    {
        $this->guardAccess($viewer);

        $focus = $focusId === null ? null : $this->messages->findWithTrashed($focusId);

        if ($focus !== null && (int) $focus->channel_id !== (int) $channel->getKey()) {
            $focus = null;
        }

        if ($focus !== null) {
            // A permalink: the page around the linked message. The cursor then sits
            // before the newest messages, which the first poll fetches in id order.
            $half = intdiv(ChatSettings::PAGE_SIZE, 2);
            $messages = $this->messages->latestInChannel((int) $channel->getKey(), $half, (int) $focus->getKey() + 1)
                ->concat($this->messages->afterId((int) $channel->getKey(), (int) $focus->getKey(), $half))
                ->values();
        } else {
            $messages = $this->history($channel, $viewer, ChatSettings::PAGE_SIZE);
        }

        $cursor = (int) ($messages->last()?->getKey() ?? $this->messages->latestIdInChannel((int) $channel->getKey()) ?? 0);
        $read = $this->reads->forUser((int) $channel->getKey(), (int) $viewer->getKey());
        $this->correspondence->notePresence($channel, $viewer);

        return [
            'messages' => ChatMessageData::collection($messages, $viewer, $this->canModerate($viewer)),
            'cursor' => $cursor,
            'since' => $this->now(),
            'lastRead' => (int) ($read?->last_read_message_id ?? 0),
            'hasOlder' => $messages->isNotEmpty() && $this->messages->countBefore((int) $channel->getKey(), (int) $messages->first()->getKey()) > 0,
            'channels' => $this->channelSummaries($channel, $viewer),
            'focus' => $focus === null ? null : (int) $focus->getKey(),
            'pins' => $this->correspondence->pins($channel, $viewer),
            'members' => $this->correspondence->members($viewer),
            'online' => $this->correspondence->online(),
        ];
    }

    /**
     * One poll: new messages after the cursor plus edits and deletions of
     * messages the client already has, and the next cursors.
     *
     * Each poll also refreshes the viewer's presence (at most once a minute)
     * and returns who is online.
     *
     * @return array{messages: list<array<string, mixed>>, changed: list<array<string, mixed>>, cursor: int, since: string, channels: list<array<string, mixed>>, pins: list<array<string, mixed>>, online: list<int>}
     */
    public function sync(ChatChannel $channel, User $viewer, int $cursor, ?string $since): array
    {
        $this->guardAccess($viewer);

        $sinceAt = $this->parseSince($since);
        $now = $this->now();
        $moderator = $this->canModerate($viewer);

        $new = $this->since($channel, $viewer, $cursor);
        $changed = $this->changedSince($channel, $viewer, $sinceAt, $cursor);
        $this->correspondence->notePresence($channel, $viewer);

        return [
            'messages' => ChatMessageData::collection($new, $viewer, $moderator),
            'changed' => ChatMessageData::collection($changed, $viewer, $moderator),
            'cursor' => (int) ($new->last()?->getKey() ?? $cursor),
            'since' => $now,
            'channels' => $this->channelSummaries($channel, $viewer),
            'pins' => $this->correspondence->pins($channel, $viewer),
            'online' => $this->correspondence->online(),
        ];
    }

    /**
     * The page of messages before a given id, for "load older on scroll".
     *
     * @return array{messages: list<array<string, mixed>>, hasOlder: bool}
     */
    public function older(ChatChannel $channel, User $viewer, int $beforeId): array
    {
        $messages = $this->history($channel, $viewer, ChatSettings::PAGE_SIZE, $beforeId);
        $first = $messages->first();

        return [
            'messages' => ChatMessageData::collection($messages, $viewer, $this->canModerate($viewer)),
            'hasOlder' => $first !== null && $this->messages->countBefore((int) $channel->getKey(), (int) $first->getKey()) > 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function present(ChatMessage $message, User $viewer): array
    {
        return ChatMessageData::from($message, $viewer, $this->canModerate($viewer));
    }

    // -- Read state ---------------------------------------------------------

    /**
     * Move the reader's cursor forward (never backwards).
     */
    public function markRead(ChatChannel $channel, User $reader, ?int $messageId = null): void
    {
        $this->guardAccess($reader);

        $messageId ??= $this->messages->latestIdInChannel((int) $channel->getKey());

        if ($messageId !== null) {
            $this->reads->markRead((int) $channel->getKey(), (int) $reader->getKey(), $messageId);
        }
    }

    public function unreadCount(ChatChannel $channel, User $reader): int
    {
        $read = $this->reads->forUser((int) $channel->getKey(), (int) $reader->getKey());

        return $this->messages->countAfter((int) $channel->getKey(), (int) ($read?->last_read_message_id ?? 0));
    }

    /**
     * @return array<int, int> channel id => unread count
     */
    public function unreadCounts(User $reader): array
    {
        return $this->messages->unreadCountsFor((int) $reader->getKey());
    }

    // -- Reports ------------------------------------------------------------

    /**
     * Report someone else's message; a second report of the same message by
     * the same user returns the first. A deleted message cannot be reported.
     *
     * @throws ChatMessageRemovedException
     */
    public function report(ChatMessage $message, User $reporter, ?string $reason = null): ChatReport
    {
        $this->guardAccess($reporter);

        if ((int) $message->user_id === (int) $reporter->getKey()) {
            throw new AuthorizationException(__('You cannot report your own message.'));
        }

        $current = $this->messages->findWithTrashed((int) $message->getKey());

        if ($current === null || $current->trashed()) {
            throw ChatMessageRemovedException::make();
        }

        $existing = $this->reports->findFor((int) $current->getKey(), (int) $reporter->getKey());

        if ($existing !== null) {
            return $existing;
        }

        $report = $this->reports->create([
            'message_id' => $current->getKey(),
            'reporter_id' => $reporter->getKey(),
            'reason' => filled($reason) ? mb_substr(trim((string) $reason), 0, ChatSettings::REPORT_REASON_MAX_LENGTH) : null,
        ]);

        $this->audit->record($reporter, AuditAction::CHAT_MESSAGE_REPORTED, $current, ['report_id' => $report->getKey()]);

        return $report;
    }

    public function resolveReport(ChatReport $report, User $moderator): ChatReport
    {
        $this->guardModerator($moderator);

        if ($report->isResolved()) {
            return $report;
        }

        $report = $this->reports->markResolved($report, (int) $moderator->getKey());
        $this->audit->record($moderator, AuditAction::CHAT_REPORT_RESOLVED, $report, ['message_id' => $report->message_id]);

        return $report;
    }

    /**
     * Delete the reported message and resolve every open report about it.
     */
    public function deleteReportedMessage(ChatReport $report, User $moderator): ChatReport
    {
        $this->guardModerator($moderator);

        $message = $this->messages->findWithTrashed((int) $report->message_id);

        if ($message !== null) {
            $this->delete($message, $moderator);
        }

        $this->reports->markResolvedForMessage((int) $report->message_id, (int) $moderator->getKey());

        return $this->resolveReport($report->refresh(), $moderator);
    }

    /**
     * Suspend the author of a reported message from the community and resolve the report.
     */
    public function suspendReportedAuthor(ChatReport $report, User $moderator, ?string $note = null): ChatReport
    {
        $this->guardModerator($moderator);

        $message = $this->messages->findWithTrashed((int) $report->message_id);
        $author = $message?->user;

        if ($author !== null && ! $author->is($moderator) && $author->community_status->canChat()) {
            $this->community->suspend($author, $moderator, $note ?? 'Suspended from a chat report.');
        }

        return $this->resolveReport($report, $moderator);
    }

    public function findReport(int $id): ?ChatReport
    {
        return $this->reports->findWithRelations($id);
    }

    /**
     * @return Collection<int, ChatReport>
     */
    public function openReports(): Collection
    {
        return $this->reports->open();
    }

    public function countOpenReports(): int
    {
        return $this->reports->countOpen();
    }

    /**
     * @return LengthAwarePaginator<int, ChatReport>
     */
    public function paginateReports(bool $open = true, int $perPage = 20): LengthAwarePaginator
    {
        return $this->reports->paginateByState($open, $perPage);
    }

    /**
     * The chat reports list: search and ChatReportRepositoryInterface::FILTER_* criteria.
     *
     * @param  array<string, string|list<string>>  $criteria
     * @return LengthAwarePaginator<int, ChatReport>
     */
    public function filterReports(?string $term, array $criteria, int $perPage = 20, bool $oldestFirst = false): LengthAwarePaginator
    {
        return $this->reports->filter($term, $criteria, $perPage, $oldestFirst);
    }

    /**
     * @param  array<string, string|list<string>>  $criteria
     */
    public function countReports(?string $term, array $criteria): int
    {
        return $this->reports->countFiltered($term, $criteria);
    }

    // -- Access -------------------------------------------------------------

    public function canModerate(User $user): bool
    {
        return ChatAccess::canModerate($user);
    }

    /**
     * Re-reads the member's current standing on every call, so a suspension
     * or removal takes effect on the very next poll or send.
     *
     * @throws AuthorizationException
     */
    public function guardAccess(User $user): void
    {
        ChatAccess::guard($user);
    }

    /**
     * @throws AuthorizationException
     */
    private function guardModerator(User $user): void
    {
        ChatAccess::guardModerator($user);
    }

    /**
     * @return list<array{id: int, name: string, slug: string, numeral: string, description: ?string, unread: int, current: bool}>
     */
    private function channelSummaries(ChatChannel $current, User $viewer): array
    {
        $unread = $this->messages->unreadCountsFor((int) $viewer->getKey());

        return $this->channels()->values()->map(fn (ChatChannel $channel, int $index): array => [
            'id' => (int) $channel->getKey(),
            'name' => $channel->name,
            'slug' => $channel->slug,
            'numeral' => $this->typesetter->roman($index + 1),
            // The default room description is fixed copy, shown in the reader's language; anything else as written.
            'description' => filled($channel->description) ? __((string) $channel->description) : null,
            'unread' => $channel->is($current) ? 0 : ($unread[(int) $channel->getKey()] ?? 0),
            'current' => $channel->is($current),
        ])->values()->all();
    }

    private function now(): string
    {
        return CarbonImmutable::now('UTC')->toIso8601ZuluString();
    }

    private function parseSince(?string $since): CarbonImmutable
    {
        if ($since === null || trim($since) === '') {
            return CarbonImmutable::now('UTC')->subMinute();
        }

        try {
            return CarbonImmutable::parse($since)->utc();
        } catch (\Throwable) {
            return CarbonImmutable::now('UTC')->subMinute();
        }
    }

    private function cleanBody(string $body, bool $allowEmpty = false): string
    {
        $body = ChatText::normalise($body);

        if ($body === '' && $allowEmpty) {
            return '';
        }

        if ($body === '') {
            throw new InvalidArgumentException(__('A message cannot be empty.'));
        }

        if (mb_strlen($body) > ChatSettings::BODY_MAX_LENGTH) {
            throw new InvalidArgumentException(__('A message may not exceed :max characters.', ['max' => ChatSettings::BODY_MAX_LENGTH]));
        }

        return $body;
    }
}
