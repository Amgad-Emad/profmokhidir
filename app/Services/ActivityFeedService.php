<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\AuditAction;
use App\Constants\ContentTypes;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * The dashboard's "Recent activity" in plain language: who did what to which
 * record ("Amina approved a tribute from Yusuf"), newest first. Sign-ins are
 * left out; they are in the audit log, not news for an editor.
 */
class ActivityFeedService
{
    public const int LIMIT = 10;

    public function __construct(private readonly AuditLogService $audit) {}

    /**
     * @return list<array{id: int, actor: string, sentence: string, at: CarbonInterface}>
     */
    public function recent(int $limit = self::LIMIT): array
    {
        return $this->audit->recentExcept([AuditAction::ADMIN_LOGIN], $limit)
            ->map(fn (AuditLog $log): array => [
                'id' => (int) $log->getKey(),
                'actor' => $this->actor($log),
                'sentence' => $this->sentence($log),
                'at' => $log->created_at,
            ])
            ->values()
            ->all();
    }

    /**
     * The whole entry as one sentence in the current language, the actor first.
     */
    public function sentence(AuditLog $log): string
    {
        [$template, $replace] = $this->template($log);

        return __($template, ['actor' => $this->actor($log), ...$replace]);
    }

    public function actor(AuditLog $log): string
    {
        return $log->user?->name ?? __('A visitor');
    }

    /**
     * The translation key of the entry (with :actor) and its other placeholders.
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private function template(AuditLog $log): array
    {
        $meta = is_array($log->meta) ? $log->meta : [];
        $name = is_string($meta['name'] ?? null) && trim($meta['name']) !== '' ? trim($meta['name']) : null;
        $label = $this->quoted($meta['label'] ?? null);
        $thing = $this->thing($log, $label);
        $person = $log->subject instanceof User ? $log->subject->name : null;
        $from = $name === null ? [] : ['name' => $name];

        return match ($log->action) {
            AuditAction::TRIBUTE_APPROVED => [$name ? ':actor approved a tribute from :name' : ':actor approved a tribute', $from],
            AuditAction::TRIBUTE_REJECTED => [$name ? ':actor declined a tribute from :name' : ':actor declined a tribute', $from],
            AuditAction::TRIBUTE_EDITED => [$name ? ':actor edited a tribute from :name' : ':actor edited a tribute', $from],
            AuditAction::TRIBUTE_DELETED => [$name ? ':actor deleted a tribute from :name' : ':actor deleted a tribute', $from],
            AuditAction::TRIBUTE_FEATURED => [$name ? ':actor featured a tribute from :name' : ':actor featured a tribute', $from],
            AuditAction::TRIBUTE_UNFEATURED => [$name ? ':actor stopped featuring a tribute from :name' : ':actor stopped featuring a tribute', $from],
            AuditAction::COMMUNITY_APPLIED => [':actor applied to join the Scientific Community', []],
            AuditAction::COMMUNITY_APPROVED => $person ? [':actor welcomed :person to the Scientific Community', ['person' => $person]] : [':actor approved a community application', []],
            AuditAction::COMMUNITY_REJECTED => $person ? [':actor declined the community application from :person', ['person' => $person]] : [':actor declined a community application', []],
            AuditAction::CONTENT_CREATED => [':actor added :thing', ['thing' => $thing]],
            AuditAction::CONTENT_PUBLISHED => [':actor published :thing', ['thing' => $thing]],
            AuditAction::CONTENT_UNPUBLISHED => [':actor moved :thing back to draft', ['thing' => $thing]],
            AuditAction::CONTENT_DELETED => [':actor moved :thing to the trash', ['thing' => $thing]],
            AuditAction::CONTENT_RESTORED => [':actor restored :thing from the trash', ['thing' => $thing]],
            AuditAction::MEDIA_UPLOADED => $label ? [':actor uploaded :file', ['file' => $label]] : [':actor uploaded a file', []],
            AuditAction::MEDIA_UPDATED => $label ? [':actor updated the details of :file', ['file' => $label]] : [':actor updated the details of an image', []],
            AuditAction::MEDIA_DELETED => $label ? [':actor deleted :file', ['file' => $label]] : [':actor deleted a file', []],
            default => [':actor :action', ['action' => AuditAction::label($log->action)]],
        };
    }

    private function thing(AuditLog $log, ?string $label): string
    {
        $type = $log->subject_type === null ? null : ContentTypes::keyForModel($log->subject_type);

        if ($type === null) {
            return $label ?? __('content');
        }

        $noun = mb_strtolower(__(ContentTypes::get($type)['singular']));

        if ($label !== null) {
            return __('the :type :label', ['type' => $noun, 'label' => $label]);
        }

        return preg_match('/^[aeiou]/', $noun) === 1 ? __('an :type', ['type' => $noun]) : __('a :type', ['type' => $noun]);
    }

    private function quoted(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? '“'.trim($value).'”' : null;
    }
}
