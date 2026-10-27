<?php

declare(strict_types=1);

namespace App\View\Components\Site;

use App\Constants\Breadcrumbs as BreadcrumbCopy;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The breadcrumb trail in small caps on inner pages. The layout computes the
 * trail (App\Services\BreadcrumbService) and prints its BreadcrumbList JSON-LD
 * in <head>; this component only renders the visible list.
 */
class Breadcrumbs extends Component
{
    /**
     * @param  list<array{label: string, url: string, current: bool}>  $trail
     */
    public function __construct(public array $trail = [], public string $tone = 'evergreen') {}

    public function ariaLabel(): string
    {
        return __(BreadcrumbCopy::ARIA_LABEL);
    }

    public function separator(): string
    {
        return BreadcrumbCopy::SEPARATOR;
    }

    public function shouldRender(): bool
    {
        return count($this->trail) > 1;
    }

    public function render(): View
    {
        return view('components.site.breadcrumbs');
    }
}
