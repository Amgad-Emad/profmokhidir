{{--
    Admin › Pages › one page: its blocks in the order they appear on the site, grouped under the
    site's own section names, each labelled in plain words with its limit. The real page is previewed
    beside the form (Alpine `pagePreview`, resources/js/admin/page-preview.js) with the draft shown,
    and reloads after every save. Below 80rem the pane is hidden and Preview opens a tab instead.
--}}
@use('App\Constants\ContentStatus')
@use('Illuminate\Support\Str')
@php
    $published = $page->status === ContentStatus::Published;
    $nav = [];
    // The About page's profile (name, biography, expertise tags, key facts, portrait, contact) sits
    // after the page's opening, in the order the page shows it.
    $profileSections = $embedsProfile ? ['identity' => __('Identity'), 'biography' => __('Biography'), 'expertise' => __('Expertise & key facts'), 'portrait' => __('Portrait photo'), 'contact' => __('Contact')] : [];
    foreach (array_keys($sections) as $position => $title) {
        $nav[Str::slug($title)] = __($title);
        if ($position === 0) {
            $nav += $profileSections;
        }
    }
    if ($records) {
        $nav['page-records'] = $records['plural'];
    }
@endphp
<div class="page-editor" x-data="pagePreview" x-bind:class="{ 'page-editor--preview': open }" data-page-editor="{{ $page->key }}">
    <x-admin.page-header :eyebrow="__('Pages')" :title="__($definition['title'])" :intro="__($definition['summary']).' '.__('You change the words and images here; the layout, typography and colours stay as designed.')">
        <x-button variant="quiet" size="sm" type="button" class="page-editor__toggle" x-on:click="open = ! open" x-bind:aria-pressed="open.toString()" data-preview-toggle>
            <x-icon name="eye" class="size-4" />{{ __('Side-by-side preview') }}
        </x-button>
    </x-admin.page-header>
    <x-admin.flash />

    @if (! $published)
        <x-alert tone="bronze" class="mt-8" data-page-unpublished>{{ __('This page is a draft: visitors see its original wording. Save to show these words.') }}</x-alert>
    @elseif ($hasDraft)
        <x-alert tone="bronze" class="mt-8" data-page-has-draft>
            <span class="flex flex-wrap items-center justify-between gap-4">
                <span>{{ __('Some changes are saved as a draft and not yet on the site. Save to put them live.') }}</span>
                <x-button variant="quiet" size="sm" type="button" wire:click="discardDraft" data-discard-draft>{{ __('Discard the draft') }}</x-button>
            </span>
        </x-alert>
    @endif

    <div class="page-editor__columns mt-10">
        <x-admin.edit-layout :editor="$editor" :sections="$nav" :autosave="true" :save-only="true" class="page-editor__form">
            @foreach ($sections as $title => $keys)
                @if ($embedsProfile && $loop->index === 1)
                    <livewire:admin.profile.edit-profile :embedded="true" wire:key="about-profile" />
                @endif
                <x-admin.form-section :id="Str::slug($title)" :title="__($title)" :collapsed="true" wire:key="section-{{ Str::slug($title) }}" @class(['form-section--hidden-on-page' => in_array($title, $hiddenSections, true)])>
                    @if (in_array($title, $hideableSections, true))
                        @php $shown = ! in_array($title, $hiddenSections, true); @endphp
                        <x-slot:actions>
                            {{-- The site's own outline pill: an eye and "Shown on the page", or a crossed eye and "Hidden" in a quiet dashed outline. --}}
                            <x-button variant="secondary" size="sm" type="button" role="switch" aria-checked="{{ $shown ? 'true' : 'false' }}" @class(['section-visibility', 'section-visibility--hidden' => ! $shown]) wire:click="toggleSectionVisibility('{{ Str::slug($title) }}')" wire:loading.attr="disabled" data-section-visibility="{{ Str::slug($title) }}">
                                <x-icon :name="$shown ? 'eye' : 'eye-off'" class="size-4" />
                                <span>{{ $shown ? __('Shown on the page') : __('Hidden') }}</span>
                                <span class="sr-only">: {{ __($title) }}</span>
                            </x-button>
                        </x-slot:actions>
                    @endif
                    @foreach ($keys as $key)
                        @include('livewire.admin.pages.partials.block', ['key' => $key, 'block' => $blocks[$key]])
                    @endforeach
                </x-admin.form-section>
            @endforeach
            @if ($embedsProfile && count($sections) < 2)
                <livewire:admin.profile.edit-profile :embedded="true" wire:key="about-profile" />
            @endif

            @if ($records)
                @include('livewire.admin.pages.partials.records', ['records' => $records])
            @endif
        </x-admin.edit-layout>

        <aside class="page-preview" x-show="open" x-cloak aria-label="{{ __('Preview of the page') }}" wire:ignore data-page-preview data-preview-urls="{{ json_encode($previewUrls, JSON_UNESCAPED_SLASHES) }}">
            <div class="page-preview__bar">
                <p class="t-label text-accent-text">{{ __('Preview with your draft') }}</p>
                <div class="page-preview__controls">
                    @if (count($previewUrls) > 1)
                        <label class="sr-only" for="preview-view">{{ __('Error page shown') }}</label>
                        <span class="page-preview__select-wrap"><select id="preview-view" class="page-preview__select" x-on:change="show($event.target.value)">
                            @foreach ($previewUrls as $view => $url)
                                <option value="{{ $view }}">{{ __('Error :code', ['code' => $view]) }}</option>
                            @endforeach
                        </select><x-icon name="chevron-down" class="page-preview__select-icon size-4" /></span>
                    @endif
                    <div class="page-preview__devices" role="group" aria-label="{{ __('Preview width') }}">
                        <button type="button" class="page-preview__device" x-on:click="device = 'desktop'" x-bind:aria-pressed="(device === 'desktop').toString()">{{ __('Desktop') }}</button>
                        <button type="button" class="page-preview__device" x-on:click="device = 'phone'" x-bind:aria-pressed="(device === 'phone').toString()">{{ __('Phone') }}</button>
                    </div>
                    <button type="button" class="page-preview__device" x-on:click="reload()">{{ __('Reload') }}</button>
                </div>
            </div>
            <div class="page-preview__viewport" x-ref="viewport" x-bind:style="viewportStyle">
                <iframe x-ref="frame" src="{{ reset($previewUrls) }}" title="{{ __('Preview of :page with the draft', ['page' => __($definition['title'])]) }}" class="page-preview__frame" x-bind:style="frameStyle" x-on:load="loading = false" loading="lazy" inert></iframe>
            </div>
            <p class="page-preview__note">{{ __('The preview shows what is saved. Changes appear here a moment after each autosave. To read or use the page itself, open Preview in the save bar: it opens the same page in a new tab.') }}</p>
        </aside>
    </div>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
