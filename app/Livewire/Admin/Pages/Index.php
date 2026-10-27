<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Pages;

use App\Constants\Pages;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Admin › Pages: every public page whose words and images editors change,
 * in site order, with its status, whether it has unpublished changes and when
 * it was last saved. Pages are fixed by the design: none is added or removed.
 */
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    public function render(PageService $pages): View
    {
        $rows = $pages->all()->map(fn (Page $page): array => [
            'page' => $page,
            'definition' => Pages::get($page->key),
            'hasDraft' => $pages->hasDraft($page),
            'needsArabic' => $pages->needsArabic($page),
            'editUrl' => route(Pages::EDIT_ROUTE, [Pages::ROUTE_PARAMETER => $page->key]),
        ]);

        return view('livewire.admin.pages.index', ['rows' => $rows])
            ->layout('components.layouts.admin', ['title' => __('Pages'), 'crumb' => __('Pages')]);
    }
}
