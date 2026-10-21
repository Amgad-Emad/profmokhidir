<?php

declare(strict_types=1);

namespace App\Support;

use App\Constants\PublicationType;

/**
 * The catalogue filters on /publications: type, an inclusive year range and a
 * topic. Every part is optional; an empty set means the whole catalogue.
 */
final readonly class PublicationFilters
{
    public function __construct(
        public ?PublicationType $type = null,
        public ?int $from = null,
        public ?int $to = null,
        public ?string $topic = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->type === null && $this->from === null && $this->to === null && $this->topic === null;
    }

    /**
     * The same filters with some parts replaced (pass null to clear a part).
     *
     * @param  array{type?: PublicationType|null, from?: int|null, to?: int|null, topic?: string|null}  $changes
     */
    public function with(array $changes): self
    {
        return new self(
            array_key_exists('type', $changes) ? $changes['type'] : $this->type,
            array_key_exists('from', $changes) ? $changes['from'] : $this->from,
            array_key_exists('to', $changes) ? $changes['to'] : $this->to,
            array_key_exists('topic', $changes) ? $changes['topic'] : $this->topic,
        );
    }

    /**
     * Query-string parameters, empty parts left out.
     *
     * @return array<string, string|int>
     */
    public function query(): array
    {
        return array_filter([
            'type' => $this->type?->value,
            'from' => $this->from,
            'to' => $this->to,
            'topic' => $this->topic,
        ], fn ($value): bool => $value !== null);
    }
}
