<?php

declare(strict_types=1);

namespace App\Livewire\Community\Chat;

use App\Constants\ChatCopy;
use App\Constants\ChatFormat;
use App\Constants\ChatSettings;
use App\Exceptions\ChatMessageConflictException;
use App\Exceptions\ChatMessageRemovedException;
use App\Exceptions\ChatRateLimitException;
use App\Exceptions\CommunityApplicationException;
use App\Http\Requests\ChatMessageRequest;
use App\Http\Requests\ChatReportRequest;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatAttachmentService;
use App\Services\ChatCorrespondenceService;
use App\Services\ChatService;
use App\Services\CommunityPageService;
use App\Services\CommunityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The one Scientific Community room. The component is a thin transport: every
 * action authorises (policy), validates (Form Request rules), calls ChatService
 * and returns plain data for the chat script, which renders the conversation.
 * Actions never re-render the component (skipRender), so a poll can never
 * morph the composer while a member types; only losing access re-renders.
 * Reliability rules (id ordering, cursors, idempotent sends, locking) live in
 * the service and repositories, not here.
 */
#[Layout('components.layouts.chat')]
class Room extends Component
{
    use AuthorizesRequests;

    public const string ERROR_ACCESS = 'access';

    public const string ERROR_RATE_LIMIT = 'rate_limit';

    public const string ERROR_CONFLICT = 'conflict';

    public const string ERROR_REMOVED = 'removed';

    public const string ERROR_INVALID = 'invalid';

    #[Locked]
    public int $channelId;

    #[Locked]
    public string $channelSlug;

    #[Locked]
    public string $channelName;

    /** Set when the member's access was taken away mid-session. */
    public bool $accessRevoked = false;

    /** @var array<string, mixed> */
    public array $initial = [];

    public function mount(): void
    {
        $channel = $this->chat()->room();
        $this->authorize('view', $channel);

        $this->channelId = (int) $channel->getKey();
        $this->channelSlug = $channel->slug;
        $this->channelName = $channel->name;

        $focus = request()->integer(ChatSettings::MESSAGE_PARAM);

        try {
            $this->initial = $this->chat()->initialState($channel, $this->viewer(), $focus > 0 ? $focus : null);
        } catch (AuthorizationException) {
            $this->accessRevoked = true;
        }
    }

    /**
     * Poll: everything after the cursor plus edits and deletions since the last check.
     *
     * @return array<string, mixed>
     */
    public function sync(int $cursor = 0, ?string $since = null): array
    {
        return $this->guarded(function () use ($cursor, $since): array {
            $channel = $this->channel();
            $this->authorize('view', $channel);

            return ['ok' => true] + $this->chat()->sync($channel, $this->viewer(), max(0, $cursor), $since);
        });
    }

    /**
     * Send, then fetch from the cursor so the sender's own message and anything
     * that arrived just before it both show.
     *
     * @return array<string, mixed>
     */
    public function send(string $body, string $clientUuid, int $cursor = 0, ?string $since = null, ?int $replyToId = null, array $attachments = []): array
    {
        return $this->guarded(function () use ($body, $clientUuid, $cursor, $since, $replyToId, $attachments): array {
            $this->authorize('create', ChatMessage::class);
            $this->validateAgainst(new ChatMessageRequest, ['body' => $body, 'client_uuid' => $clientUuid, 'reply_to_id' => $replyToId, 'attachments' => $attachments]);

            $channel = $this->channel();
            $message = $this->chat()->send($channel, $this->viewer(), $body, $clientUuid, $replyToId, array_values($attachments));

            return [
                'ok' => true,
                'message' => $this->chat()->present($message, $this->viewer()),
                'sync' => $this->chat()->sync($channel, $this->viewer(), max(0, $cursor), $since),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function loadOlder(int $beforeId): array
    {
        return $this->guarded(function () use ($beforeId): array {
            $channel = $this->channel();
            $this->authorize('view', $channel);

            return ['ok' => true] + $this->chat()->older($channel, $this->viewer(), $beforeId);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function edit(int $messageId, string $body, int $version): array
    {
        return $this->guarded(function () use ($messageId, $body, $version): array {
            $message = $this->message($messageId);
            $this->authorize('update', $message);
            $this->validateAgainst(new ChatMessageRequest, ['body' => $body, 'client_uuid' => $message->client_uuid, 'version' => $version]);

            $edited = $this->chat()->edit($message, $this->viewer(), $body, $version);

            return ['ok' => true, 'message' => $this->chat()->present($edited, $this->viewer())];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(int $messageId): array
    {
        return $this->guarded(function () use ($messageId): array {
            $message = $this->message($messageId);
            $this->authorize('delete', $message);

            $this->chat()->delete($message, $this->viewer());

            return ['ok' => true, 'message' => $this->chat()->present($this->message($messageId), $this->viewer())];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function report(int $messageId, string $reason): array
    {
        return $this->guarded(function () use ($messageId, $reason): array {
            $message = $this->message($messageId);
            $this->authorize('report', $message);
            $this->validateAgainst(new ChatReportRequest, ['reason' => $reason]);

            $this->chat()->report($message, $this->viewer(), $reason);

            return ['ok' => true, 'notice' => 'Thank you. An administrator will look at this message.'];
        });
    }

    /**
     * Moderators pin a message to the channel's side panel; the new pin list
     * comes back so every open tab agrees on the next poll.
     *
     * @return array<string, mixed>
     */
    public function pin(int $messageId): array
    {
        return $this->setPinned($messageId, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function unpin(int $messageId): array
    {
        return $this->setPinned($messageId, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function setPinned(int $messageId, bool $pinned): array
    {
        return $this->guarded(function () use ($messageId, $pinned): array {
            $message = $this->message($messageId);

            if ($message->trashed()) {
                throw ChatMessageRemovedException::make();
            }

            $this->authorize('pin', $message);

            $message = $pinned
                ? $this->correspondence()->pin($message, $this->viewer())
                : $this->correspondence()->unpin($message, $this->viewer());

            return [
                'ok' => true,
                'message' => $this->chat()->present($message, $this->viewer()),
                'pins' => $this->correspondence()->pins($this->channel(), $this->viewer()),
            ];
        });
    }

    /**
     * Search within this channel.
     *
     * @return array<string, mixed>
     */
    public function search(string $term): array
    {
        return $this->guarded(function () use ($term): array {
            $channel = $this->channel();
            $this->authorize('view', $channel);

            return ['ok' => true] + $this->correspondence()->search($channel, $this->viewer(), $term);
        });
    }

    /**
     * An administrator suspends a member from the Members tab (the same
     * service and policy as the community screen in /admin).
     *
     * @return array<string, mixed>
     */
    public function suspendMember(int $userId): array
    {
        return $this->guarded(function () use ($userId): array {
            $member = $this->community()->find($userId);

            if ($member === null) {
                throw new InvalidArgumentException('That member could not be found.');
            }

            $this->authorize('review', $member);

            if ($member->is($this->viewer())) {
                throw new InvalidArgumentException('You cannot suspend yourself.');
            }

            try {
                $this->community()->suspend($member, $this->viewer(), ChatCopy::MEMBER_SUSPEND_NOTE);
            } catch (CommunityApplicationException $exception) {
                throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
            }

            return [
                'ok' => true,
                'notice' => strtr(ChatCopy::MEMBER_SUSPENDED, [':name' => $member->name]),
                'members' => $this->correspondence()->members($this->viewer()),
            ];
        });
    }

    /**
     * The current state of one message, for a client that was told its copy is stale.
     *
     * @return array<string, mixed>
     */
    public function reload(int $messageId): array
    {
        return $this->guarded(function () use ($messageId): array {
            $message = $this->message($messageId);
            $this->authorize('view', $message);

            return ['ok' => true, 'message' => $this->chat()->present($message, $this->viewer())];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function markRead(int $messageId): array
    {
        return $this->guarded(function () use ($messageId): array {
            $channel = $this->channel();
            $this->authorize('view', $channel);

            $this->chat()->markRead($channel, $this->viewer(), max(0, $messageId));

            return ['ok' => true];
        });
    }

    public function render(): View
    {
        return view('livewire.community.chat.room', [
            'config' => ChatSettings::clientConfig() + ['attachments' => app(ChatAttachmentService::class)->clientConfig()],
            'communityUrl' => route(CommunityPageService::ROUTE_INDEX),
            'suspendedHeading' => $this->communityCopy()->text('suspended_heading'),
            'suspendedMessage' => $this->communityCopy()->text('suspended'),
            'communityLabel' => $this->communityCopy()->text('title'),
            'chatIntro' => $this->communityCopy()->text('chat_intro'),
            'conduct' => $this->communityCopy()->conductRules(),
            'hints' => ChatFormat::hints(),
            'canModerate' => ! $this->accessRevoked && $this->chat()->canModerate($this->viewer()),
        ])->title(ChatSettings::ROOM_NAME.' · '.ChatCopy::TITLE);
    }

    /**
     * Run an action and turn every expected failure into data the script can
     * show, so the member never sees a blank 500. Losing access is the one
     * failure that also changes the page: it switches to the suspension notice.
     *
     * @param  callable(): array<string, mixed>  $action
     * @return array<string, mixed>
     */
    private function guarded(callable $action): array
    {
        if ($this->accessRevoked) {
            return $this->failure(self::ERROR_ACCESS, $this->communityCopy()->text('suspended'));
        }

        $result = $this->attempt($action);

        // The script renders the room; a server render would morph the composer under the
        // member's fingers. Only losing access changes the page (to the suspension notice).
        if (! $this->accessRevoked) {
            $this->skipRender();
        }

        return $result;
    }

    /**
     * @param  callable(): array<string, mixed>  $action
     * @return array<string, mixed>
     */
    private function attempt(callable $action): array
    {
        try {
            return $action();
        } catch (AuthorizationException $exception) {
            if (! $this->viewer()->canAccessChat()) {
                $this->accessRevoked = true;

                return $this->failure(self::ERROR_ACCESS, $this->communityCopy()->text('suspended'));
            }

            return $this->failure(self::ERROR_ACCESS, $exception->getMessage() ?: 'You may not do that.');
        } catch (ChatRateLimitException $exception) {
            return $this->failure(self::ERROR_RATE_LIMIT, $exception->getMessage(), ['retry_after' => $exception->retryAfter]);
        } catch (ChatMessageConflictException $exception) {
            return $this->failure(self::ERROR_CONFLICT, $exception->getMessage());
        } catch (ChatMessageRemovedException $exception) {
            return $this->failure(self::ERROR_REMOVED, $exception->getMessage());
        } catch (ValidationException $exception) {
            return $this->failure(self::ERROR_INVALID, (string) collect($exception->errors())->flatten()->first());
        } catch (InvalidArgumentException $exception) {
            return $this->failure(self::ERROR_INVALID, $exception->getMessage());
        }
    }

    /**
     * The community page's copy (Admin › Pages), shared with the chat.
     */
    private function communityCopy(): CommunityPageService
    {
        return app(CommunityPageService::class);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function failure(string $code, string $message, array $extra = []): array
    {
        return ['ok' => false, 'error' => $code, 'message' => $message] + $extra;
    }

    /**
     * Apply a Form Request's rules to action arguments.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validateAgainst(FormRequest $request, array $data): void
    {
        Validator::make($data, $request->rules(), $request->messages(), $request->attributes())->validate();
    }

    private function channel(): ChatChannel
    {
        $channel = $this->chat()->findChannel($this->channelSlug);

        if ($channel === null || (int) $channel->getKey() !== $this->channelId) {
            abort(404);
        }

        return $channel;
    }

    private function message(int $id): ChatMessage
    {
        $message = $this->chat()->findMessage($id);

        if ($message === null || (int) $message->channel_id !== $this->channelId) {
            throw ChatMessageRemovedException::make();
        }

        return $message;
    }

    private function viewer(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private function chat(): ChatService
    {
        return app(ChatService::class);
    }

    private function correspondence(): ChatCorrespondenceService
    {
        return app(ChatCorrespondenceService::class);
    }

    private function community(): CommunityService
    {
        return app(CommunityService::class);
    }
}
