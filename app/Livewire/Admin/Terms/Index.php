<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Terms;

use App\Constants\AdminNavigation;
use App\Constants\Locales;
use App\Constants\TermVocabulary;
use App\Http\Requests\Admin\TermTranslationRequest;
use App\Livewire\Admin\Concerns\NotifiesEditors;
use App\Models\TermTranslation;
use App\Services\TermTranslationService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Arabic names for the categories, types, courses, topics and tags editors
 * type in English: each term in use, grouped by where it is used, with its
 * Arabic name. A blank name leaves the English showing on Arabic pages.
 */
class Index extends Component
{
    use AuthorizesRequests, NotifiesEditors;

    /** @var list<array{vocabulary: string, term: string, label: string}> */
    public array $rows = [];

    public function mount(): void
    {
        $this->authorize('viewAny', TermTranslation::class);
        $this->load();
    }

    public function save(): void
    {
        $this->authorize('update', TermTranslation::class);

        $request = new TermTranslationRequest;
        $validated = $this->validate($request->rules(), [], $request->attributes())['rows'] ?? [];

        $names = [];

        foreach ($validated as $row) {
            $names[$row['vocabulary']][$row['term']] = $row['label'] ?? null;
        }

        $this->terms()->save($names, Locales::AR, auth()->user());
        $this->load();
        $this->toast(__('Arabic names saved.'));
    }

    public function render(): View
    {
        $groups = [];

        foreach ($this->rows as $index => $row) {
            $groups[$row['vocabulary']][$index] = $row;
        }

        return view('livewire.admin.terms.index', [
            'groups' => $groups,
            'vocabularies' => collect(TermVocabulary::cases())->keyBy(fn (TermVocabulary $vocabulary): string => $vocabulary->value),
        ])->layout('components.layouts.admin', ['title' => AdminNavigation::get(AdminNavigation::TERMS)['label']]);
    }

    private function load(): void
    {
        $this->rows = [];

        foreach ($this->terms()->forEditing(Locales::AR) as $group) {
            foreach ($group['terms'] as $term => $label) {
                $this->rows[] = ['vocabulary' => $group['vocabulary']->value, 'term' => (string) $term, 'label' => $label];
            }
        }
    }

    private function terms(): TermTranslationService
    {
        return app(TermTranslationService::class);
    }
}
