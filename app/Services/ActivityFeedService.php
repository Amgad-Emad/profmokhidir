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
     * The whole line: "<actor> <what happened>".
     */
    public function sentence(AuditLog $log): string
    {
        return $this->actor($log).' '.$this->predicate($log);
    }

    public function actor(AuditLog $log): string
    {
        return $log->user?->name ?? 'A visitor';
    }

    /**
     * What happened, after the actor's name.
     */
    public function predicate(AuditLog $log): string
    {
        $meta = is_array($log->meta) ? $log->meta : [];
        $name = is_string($meta['name'] ?? null) && trim($meta['name']) !== '' ? trim($meta['name']) : null;
        $label = $this->quoted($meta['label'] ?? null);
        $thing = $this->thing($log, $label);
        $person = $log->subject instanceof User ? $log->subject->name : null;

        return match ($log->action) {
            AuditAction::TRIBUTE_APPROVED => 'approved a tribute'.($name ? ' from '.$name : ''),
            AuditAction::TRIBUTE_REJECTED => 'declined a tribute'.($name ? ' from '.$name : ''),
            AuditAction::TRIBUTE_EDITED => 'edited a tribute'.($name ? ' from '.$name : ''),
            AuditAction::TRIBUTE_DELETED => 'deleted a tribute'.($name ? ' from '.$name : ''),
            AuditAction::TRIBUTE_FEATURED => 'featured a tribute'.($name ? ' from '.$name : ''),
            AuditAction::TRIBUTE_UNFEATURED => 'stopped featuring a tribute'.($name ? ' from '.$name : ''),
            AuditAction::COMMUNITY_APPLIED => 'applied to join the Scientific Community',
            AuditAction::COMMUNITY_APPROVED => $person ? 'welcomed '.$person.' to the Scientific Community' : 'approved a community application',
            AuditAction::COMMUNITY_REJECTED => $person ? 'declined the community application from '.$person : 'declined a community application',
            AuditAction::CONTENT_CREATED => 'added '.$thing,
            AuditAction::CONTENT_PUBLISHED => 'published '.$thing,
            AuditAction::CONTENT_UNPUBLISHED => 'moved '.$thing.' back to draft',
            AuditAction::CONTENT_DELETED => 'moved '.$thing.' to the trash',
            AuditAction::CONTENT_RESTORED => 'restored '.$thing.' from the trash',
            AuditAction::MEDIA_UPLOADED => 'uploaded '.($label ?? 'a file'),
            AuditAction::MEDIA_UPDATED => 'updated the details of '.($label ?? 'an image'),
            AuditAction::MEDIA_DELETED => 'deleted '.($label ?? 'a file'),
            default => AuditAction::label($log->action),
        };
    }

    /**
     * "the publication “Soil Fertility Handbook”", or "a publication" without a title.
     */
    private function thing(AuditLog $log, ?string $label): string
    {
        $type = $log->subject_type === null ? null : ContentTypes::keyForModel($log->subject_type);
        $noun = $type === null ? 'content' : strtolower(ContentTypes::get($type)['singular']);

        if ($label !== null) {
            return $type === null ? $label : 'the '.$noun.' '.$label;
        }

        return $type === null ? 'content' : (preg_match('/^[aeiou]/', $noun) === 1 ? 'an ' : 'a ').$noun;
    }

    private function quoted(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? '“'.trim($value).'”' : null;
    }
}
