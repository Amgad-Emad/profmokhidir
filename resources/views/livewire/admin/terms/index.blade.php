<div>
    <x-admin.page-header :eyebrow="__('Content')" :title="__('Arabic names')" :intro="__('Categories, types, courses, topics and tags are typed in English when content is edited. Give each one its Arabic name here; it is used on every Arabic page. A term left blank shows in English.')" />
    <div class="mt-8"><x-admin.flash /></div>

    @if ($groups === [])
        <x-empty-state class="mt-6" :message="__('No categories, courses, topics or tags are in use yet.')" />
    @else
        <form wire:submit="save" class="mt-4 max-w-3xl space-y-12" novalidate data-term-translations>
            <x-form.error-summary after-save :intro="\App\Constants\FormControls::ERROR_SUMMARY_INTRO" />
            @foreach ($groups as $vocabulary => $rows)
                <fieldset class="form-grid" data-vocabulary="{{ $vocabulary }}">
                    <legend class="t-label text-accent-text">{{ $vocabularies[$vocabulary]->label() }}</legend>
                    @foreach ($rows as $index => $row)
                        <x-form.input :model="'rows.'.$index.'.label'" :label="$vocabularies[$vocabulary]->english($row['term'])" dir="rtl" lang="ar" :maxlength="\App\Http\Requests\Admin\TermTranslationRequest::LABEL_MAX" />
                    @endforeach
                </fieldset>
            @endforeach
            <div class="flex flex-wrap gap-3">
                <x-button type="submit" wire:loading.attr="disabled" data-save>{{ __('Save Arabic names') }}</x-button>
            </div>
        </form>
    @endif
</div>
