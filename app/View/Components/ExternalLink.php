<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * A link that leaves the site. The visible text says where it goes
 * ("Source · example.org") and it opens in the same tab; only files open in a
 * new tab (see FileLink).
 */
class ExternalLink extends Component
{
    public string $host;

    public function __construct(
        public string $href,
        public string $label = 'Visit',
    ) {
        $this->host = self::hostOf($href);
    }

    public static function hostOf(string $url): string
    {
        $host = parse_url(trim($url), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return '';
        }

        return (string) preg_replace('/^www\./i', '', strtolower($host));
    }

    public function text(): string
    {
        return $this->host === '' ? $this->label : $this->label.' · '.$this->host;
    }

    public function render(): View|Closure|string
    {
        return view('components.external-link');
    }
}
