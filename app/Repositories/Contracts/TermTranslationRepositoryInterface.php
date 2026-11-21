<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Constants\TermVocabulary;

interface TermTranslationRepositoryInterface
{
    /**
     * Every stored name in one language: vocabulary value => term => name.
     *
     * @return array<string, array<string, string>>
     */
    public function labels(string $locale): array;

    /**
     * Set the names of a vocabulary's terms in one language. A blank name
     * removes that term's translation; terms not given are left as they are.
     *
     * @param  array<string, string|null>  $labels  term => name
     * @return int how many translations were written or removed
     */
    public function put(TermVocabulary $vocabulary, string $locale, array $labels): int;
}
