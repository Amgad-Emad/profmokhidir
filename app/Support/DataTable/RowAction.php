<?php

declare(strict_types=1);

namespace App\Support\DataTable;

/**
 * One action of a data table row: a link (`href`) or a Livewire call (`call`,
 * built with RowAction::call()). `inline` actions show as icon buttons at the
 * end of the row on hover or focus (and in the "…" menu on touch screens and
 * cards); the rest live in the menu only. `hook` names the data attribute the
 * button carries (data-delete...), so tests and scripts can find it; `data`
 * adds attributes with values (data-restore-revision => 4).
 */
final readonly class RowAction
{
    public function __construct(
        public string $label,
        public string $icon,
        public ?string $href = null,
        public ?string $call = null,
        public bool $inline = false,
        public bool $danger = false,
        public bool $newTab = false,
        public ?string $hook = null,
        public bool $disabled = false,
        /** @var array<string, string|int> */
        public array $data = [],
    ) {}

    public static function link(string $label, string $icon, string $href, bool $inline = false, bool $newTab = false, ?string $hook = null): self
    {
        return new self($label, $icon, href: $href, inline: $inline, newTab: $newTab, hook: $hook);
    }

    /**
     * @param  list<int|string|array<int|string>>  $arguments
     */
    public static function wire(string $label, string $icon, string $method, array $arguments = [], bool $inline = false, bool $danger = false, ?string $hook = null, bool $disabled = false, array $data = []): self
    {
        return new self($label, $icon, call: self::call($method, $arguments), inline: $inline, danger: $danger, hook: $hook, disabled: $disabled, data: $data);
    }

    /**
     * A Livewire call expression: call('trash', [4]) is "trash(4)", call('confirm', ['purge', 4])
     * is "confirm('purge', 4)" (strings single-quoted, lists as JSON).
     *
     * @param  list<mixed>  $arguments
     */
    public static function call(string $method, array $arguments = []): string
    {
        $encoded = array_map(fn (mixed $argument): string => is_string($argument)
            ? "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $argument)."'"
            : json_encode($argument, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR), $arguments);

        return $method.'('.implode(', ', $encoded).')';
    }
}
