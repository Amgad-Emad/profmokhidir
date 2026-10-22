<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\ContentTypes;
use App\Constants\Preview;
use App\Support\PreviewContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * Signed preview links for the edit screens: the record's real public page
 * with `preview=<type>.<id>`, signed and expiring after Preview::TTL_MINUTES.
 * EnablePreview honours the link; anyone holding it may look, so it can be
 * sent to a family member for checking before the record is published.
 */
class PreviewService
{
    public function __construct(private readonly PreviewContext $context) {}

    /**
     * The preview link of a record, or null when it has no public page of its
     * own to show it on (it needs a slug before it can be addressed).
     */
    public function url(string $type, Model $record): ?string
    {
        $definition = ContentTypes::get($type);
        $parameters = [];

        if ($definition['public_param'] !== null) {
            $value = $record->getAttribute($definition['public_param']);

            if (! is_string($value) || $value === '') {
                return null;
            }

            $parameters[] = $value;
        }

        $parameters[Preview::PARAM] = Preview::value($type, (int) $record->getKey());

        return URL::temporarySignedRoute($definition['public_route'], now()->addMinutes(Preview::TTL_MINUTES), $parameters);
    }

    /**
     * Whether this request is rendering a signed preview (the public layout shows a banner).
     */
    public function active(): bool
    {
        return $this->context->active();
    }
}
