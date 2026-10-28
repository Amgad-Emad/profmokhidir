<?php

declare(strict_types=1);

namespace App\Support\ListToolbar;

use App\Constants\ListFilterType;
use App\Constants\ListToolbar;

/**
 * One filter of an admin list toolbar, declared by the screen: its address
 * key, its label, how it is chosen, its options (value => label) and the value
 * it takes when the screen opens fresh (null: not filtered). `toolbar` false
 * keeps it out of the toolbar's own buttons (it is still in the filters sheet,
 * the views and the chips); `chip` is the short wording of an on/off filter's chip.
 */
final readonly class ListFilter
{
    /**
     * @param  array<string, string>  $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public ListFilterType $type,
        public array $options,
        public ?string $default = null,
        public bool $toolbar = true,
        public ?string $chip = null,
    ) {}

    /**
     * @param  array<string|int, string>  $options
     */
    public static function single(string $key, string $label, array $options, ?string $default = null): self
    {
        return new self($key, $label, ListFilterType::Single, self::keyed($options), $default);
    }

    /**
     * @param  array<string|int, string>  $options
     */
    public static function multi(string $key, string $label, array $options): self
    {
        return new self($key, $label, ListFilterType::Multi, self::keyed($options));
    }

    /**
     * An on/off filter: `label` names the button ("Language"), `option` the condition
     * ("Needs Arabic translation"), `chip` the chip ("Needs Arabic").
     */
    public static function flag(string $key, string $label, string $option, ?string $chip = null, bool $toolbar = true): self
    {
        return new self($key, $label, ListFilterType::Flag, [ListToolbar::FLAG_ON => $option], null, $toolbar, $chip);
    }

    /**
     * The values of this filter in a filter state (several for a multi-choice
     * filter), keeping only known options.
     *
     * @param  array<string, string>  $filters
     * @return list<string>
     */
    public function values(array $filters): array
    {
        $raw = (string) ($filters[$this->key] ?? '');

        if ($raw === '') {
            return [];
        }

        if ($this->type !== ListFilterType::Multi) {
            return array_key_exists($raw, $this->options) ? [$raw] : [];
        }

        // Always in the options' order, so the same choice reads (and is stored) the same way.
        return array_values(array_intersect(array_map('strval', array_keys($this->options)), explode(ListToolbar::VALUE_SEPARATOR, $raw)));
    }

    /**
     * The filter state value for a list of values ('' when none).
     *
     * @param  list<string>  $values
     */
    public function join(array $values): string
    {
        $known = array_values(array_filter($values, fn (string $value): bool => array_key_exists($value, $this->options)));

        if ($this->type !== ListFilterType::Multi) {
            return $known[0] ?? '';
        }

        // Keep the options' own order so the same choice always reads the same in the address.
        return implode(ListToolbar::VALUE_SEPARATOR, array_values(array_intersect(array_map('strval', array_keys($this->options)), $known)));
    }

    /**
     * @param  array<string|int, string>  $options
     * @return array<string, string>
     */
    private static function keyed(array $options): array
    {
        $keyed = [];

        foreach ($options as $value => $label) {
            $keyed[(string) $value] = $label;
        }

        return $keyed;
    }
}
