<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Constants\TermVocabulary;
use App\Models\TermTranslation;
use App\Repositories\Contracts\TermTranslationRepositoryInterface;

class EloquentTermTranslationRepository implements TermTranslationRepositoryInterface
{
    public function __construct(private readonly TermTranslation $model) {}

    public function labels(string $locale): array
    {
        $labels = [];

        foreach ($this->model->newQuery()->where('locale', $locale)->orderBy('term')->get() as $row) {
            $labels[$row->vocabulary->value][(string) $row->term] = (string) $row->label;
        }

        return $labels;
    }

    public function put(TermVocabulary $vocabulary, string $locale, array $labels): int
    {
        $changed = 0;

        foreach ($labels as $term => $label) {
            $keys = ['vocabulary' => $vocabulary->value, 'term' => (string) $term, 'locale' => $locale];
            $label = trim((string) $label);

            if ($label === '') {
                $changed += $this->model->newQuery()->where($keys)->delete();

                continue;
            }

            $row = $this->model->newQuery()->updateOrCreate($keys, ['label' => $label]);
            $changed += $row->wasRecentlyCreated || $row->wasChanged() ? 1 : 0;
        }

        return $changed;
    }
}
