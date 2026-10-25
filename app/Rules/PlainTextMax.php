<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rich text no longer than a number of characters as a reader counts them:
 * the markup the editor adds (paragraphs, emphasis, links) does not count.
 */
class PlainTextMax implements ValidationRule
{
    public function __construct(private readonly int $max) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $text = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if (mb_strlen($text) > $this->max) {
            $fail(sprintf('The :attribute must be no longer than %d characters (it has %d).', $this->max, mb_strlen($text)));
        }
    }
}
