<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\FormControls;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;

/**
 * Everything a field in resources/views/components/form needs to know about itself,
 * worked out once: its id and the ids of its hint, error and counter; the hint line
 * with "Required" or "Optional" in front; the first validation message for it (from a
 * plain form's error bag or a Livewire component's); and the attributes of the control
 * (name or wire:model, required, aria-invalid, aria-describedby, the data the browser
 * script needs for blur validation).
 */
final class FormFieldService
{
    /**
     * @param  array{
     *     name?: string|null,
     *     model?: string|null,
     *     wire?: string|null,
     *     id?: string|null,
     *     label?: string|null,
     *     hint?: string|null,
     *     required?: bool,
     *     optional?: bool|null,
     *     bag?: string,
     *     maxlength?: int|string|null,
     *     counter?: bool,
     *     nested?: bool,
     *     error?: string|null,
     *     marker?: bool,
     * }  $options
     * @return array{
     *     id: string, key: string, name: string|null, model: string|null, wire: string,
     *     hintId: string, errorId: string, countId: string, label: string,
     *     requirement: string|null, hint: string, hintText: string, error: string|null, invalid: bool,
     *     maxlength: int|null, counter: bool, describedBy: string, required: bool
     * }
     */
    public function describe(array $options, ViewErrorBag|MessageBag|null $errors = null): array
    {
        $name = $options['name'] ?? null;
        $model = $options['model'] ?? null;
        $key = $this->errorKey($model ?? (string) $name);
        $id = $options['id'] ?? $this->idFor($model, (string) $name);
        $required = (bool) ($options['required'] ?? false);
        $maxlength = isset($options['maxlength']) && $options['maxlength'] !== '' ? (int) $options['maxlength'] : null;
        $counter = (bool) ($options['counter'] ?? $maxlength !== null);
        $requirement = ($options['marker'] ?? true) ? $this->requirement($required, $options['optional'] ?? true) : null;
        $hint = $this->hintLine($requirement, $options['hint'] ?? null);
        $error = $options['error'] ?? $this->firstError($errors, $key, (string) ($options['bag'] ?? 'default'), (bool) ($options['nested'] ?? false));

        $field = [
            'id' => $id,
            'key' => $key,
            'name' => $name,
            'model' => $model,
            'wire' => (string) ($options['wire'] ?? 'live.blur'),
            'hintId' => $id.'-hint',
            'errorId' => $id.'-error',
            'countId' => $id.'-count',
            'label' => (string) ($options['label'] ?? ''),
            'requirement' => $requirement,
            'hint' => $hint,
            'hintText' => trim((string) ($options['hint'] ?? '')),
            'error' => $error,
            'invalid' => $error !== null,
            'maxlength' => $maxlength,
            'counter' => $counter && $maxlength !== null,
            'required' => $required,
        ];

        $field['describedBy'] = implode(' ', array_filter([
            $hint !== '' ? $field['hintId'] : null,
            $field['counter'] ? $field['countId'] : null,
            $error !== null ? $field['errorId'] : null,
        ]));

        return $field;
    }

    /**
     * The control's own attributes. Later sources win over the computed ones except
     * aria-describedby, which is joined so a component can add its own ids. Values that
     * come from props and PHP ($extra) are HTML-escaped here; $escaped takes attributes
     * Blade has already escaped (a component's $attributes), which are printed as they are.
     *
     * @param  array<string, mixed>  $field  from describe()
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>  $escaped
     */
    public function controlAttributes(array $field, array $extra = [], array $escaped = []): ComponentAttributeBag
    {
        $describedBy = trim($field['describedBy'].' '.($extra['aria-describedby'] ?? '').' '.($escaped['aria-describedby'] ?? ''));
        unset($extra['aria-describedby'], $escaped['aria-describedby']);

        $computed = [
            'id' => $field['id'],
            'name' => $field['model'] === null ? $field['name'] : null,
            $this->wireAttribute($field) => $field['model'],
            'required' => $field['required'] && $field['model'] === null,
            'aria-required' => $field['required'] && $field['model'] !== null ? 'true' : null,
            'maxlength' => $field['maxlength'],
            'aria-invalid' => $field['invalid'] ? 'true' : null,
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
            'data-label' => $field['label'] !== '' ? Str::lower($field['label']) : null,
        ];

        $attributes = array_merge(array_map($this->escape(...), array_merge($computed, $extra)), $escaped);

        return new ComponentAttributeBag(array_filter(
            $attributes,
            fn ($value, $key): bool => $key !== '' && $value !== null && $value !== false && $value !== '',
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /** Strings and numbers are escaped for an attribute; booleans and null keep their meaning. */
    public function escape(mixed $value): mixed
    {
        return is_string($value) || is_int($value) || is_float($value) ? e((string) $value) : $value;
    }

    /**
     * The messages forms.js shows when a plain form's field is left (or the form is
     * sent) in a state the browser can check, each naming the fix. Livewire fields are
     * checked on the server instead, so they carry none.
     *
     * @param  array<string, mixed>  $field  from describe()
     * @param  array{min?: int|string|null, max?: int|string|null, minlength?: int|string|null}  $limits
     * @return array<string, string>
     */
    public function browserMessages(array $field, string $kind, array $limits = []): array
    {
        if ($field['model'] !== null) {
            return [];
        }

        $label = Str::lower((string) $field['label']);
        $messages = [];

        if ($field['required']) {
            $messages['data-msg-missing'] = match ($kind) {
                'select', 'radio', 'combobox' => strtr(FormControls::MESSAGE_CHOOSE, [':label' => $label]),
                'check' => FormControls::MESSAGE_CHECK,
                'code' => strtr(FormControls::MESSAGE_CODE, [':length' => (string) ($limits['max'] ?? 6)]),
                default => strtr(FormControls::MESSAGE_REQUIRED, [':label' => $label]),
            };
        }

        $messages += match ($kind) {
            'email' => ['data-msg-type' => FormControls::MESSAGE_EMAIL],
            'url' => ['data-msg-type' => FormControls::MESSAGE_URL],
            'year' => ['data-msg-range' => FormControls::yearMessage(isset($limits['min']) ? (int) $limits['min'] : null, isset($limits['max']) ? (int) $limits['max'] : null)],
            'date' => ['data-msg-range' => FormControls::MESSAGE_DATE],
            'code' => ['data-msg-range' => strtr(FormControls::MESSAGE_CODE, [':length' => (string) ($limits['max'] ?? 6)])],
            'number' => isset($limits['min'], $limits['max'])
                ? ['data-msg-range' => strtr(FormControls::MESSAGE_RANGE, [':min' => (string) $limits['min'], ':max' => (string) $limits['max']])]
                : ['data-msg-range' => FormControls::MESSAGE_NUMBER],
            default => [],
        };

        if ($field['maxlength'] !== null) {
            $messages['data-msg-long'] = strtr(FormControls::MESSAGE_TOO_LONG, [':max' => (string) $field['maxlength']]);
        }

        if (isset($limits['minlength'])) {
            $messages['data-msg-short'] = strtr(FormControls::MESSAGE_TOO_SHORT, [':min' => (string) $limits['minlength']]);
        }

        return $messages;
    }

    /** "Required", "Optional", or nothing when the field opts out of the marker. */
    public function requirement(bool $required, ?bool $optional = true): ?string
    {
        if ($required) {
            return FormControls::REQUIRED;
        }

        return $optional === false ? null : FormControls::OPTIONAL;
    }

    public function hintLine(?string $requirement, ?string $hint): string
    {
        return implode(FormControls::HINT_SEPARATOR, array_filter([$requirement, $hint !== null ? trim($hint) : null], fn (?string $part): bool => $part !== null && $part !== ''));
    }

    /**
     * The first message for a field; with $nested, also messages of its items
     * (tags.0, key_facts.1.label), so a list control shows what is wrong inside it.
     */
    public function firstError(ViewErrorBag|MessageBag|null $errors, string $key, string $bag = 'default', bool $nested = false): ?string
    {
        if ($errors === null || $key === '') {
            return null;
        }

        $messages = $errors instanceof ViewErrorBag ? $errors->getBag($bag) : $errors;

        if ($messages->has($key)) {
            return $messages->first($key);
        }

        return $nested && $messages->has($key.'.*') ? $messages->first($key.'.*') : null;
    }

    /** form.key_facts.0.label → form-key-facts-0-label; tags[] → tags. */
    public function idFor(?string $model, string $name): string
    {
        if ($model !== null && $model !== '') {
            return str_replace(['.', '_'], '-', $model);
        }

        return trim(str_replace(['[]', '[', ']'], ['', '-', ''], $name), '-');
    }

    /** tags[] → tags, links[0][url] → links.0.url (the key Laravel files the message under). */
    public function errorKey(string $name): string
    {
        return trim(str_replace(['[]', '][', '[', ']'], ['', '.', '.', ''], $name), '.');
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function wireAttribute(array $field): string
    {
        if ($field['model'] === null) {
            return '';
        }

        return $field['wire'] === '' ? 'wire:model' : 'wire:model.'.$field['wire'];
    }
}
