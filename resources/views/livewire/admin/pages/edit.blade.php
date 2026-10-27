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
    foreach (array_keys($sections) as $title) {
        $nav[Str::slug($title)] = __($title);
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
        <x-alert tone="bronze" class="mt-8" data-page-unpublished>{{ __('This page is a draft: visitors see its original wording. Publish to show these words.') }}</x-alert>
    @elseif ($hasDraft)
        <x-alert tone="bronze" class="mt-8" data-page-has-draft>
            <span class="flex flex-wrap items-center justify-between gap-4">
                <span>{{ __('Some changes are saved as a draft and not yet on the site. Publish changes to put them live.') }}</span>
                <x-button variant="quiet" size="sm" type="button" wire:click="discardDraft" data-discard-draft>{{ __('Discard the draft') }}</x-button>
            </span>
        </x-alert>
    @endif

    <div class="page-editor__columns mt-10">
        <x-admin.edit-layout :editor="$editor" :sections="$nav" :autosave="true" :draftable="true" :publish-label="$published ? __('Publish changes') : null" class="page-editor__form">
            @foreach ($sections as $title => $keys)
                <x-admin.form-section :id="Str::slug($title)" :title="__($title)" wire:key="section-{{ Str::slug($title) }}">
                    @foreach ($keys as $key)
                        @include('livewire.admin.pages.partials.block', ['key' => $key, 'block' => $blocks[$key]])
                    @endforeach
                </x-admin.form-section>
            @endforeach
        </x-admin.edit-layout>

        <aside class="page-preview" x-show="open" x-cloak :aria-label="__('Preview of the page')" wire:ignore data-page-preview data-preview-urls="{{ json_encode($previewUrls, JSON_UNESCAPED_SLASHES) }}">
            <div class="page-preview__bar">
                <p class="t-label text-accent-text">{{ __('Preview with your draft') }}</p>
                <div class="page-preview__controls">
                    @if (count($previewUrls) > 1)
                        <label class="sr-only" for="preview-view">{{ __('Error page shown') }}</label>
                        <select id="preview-view" class="page-preview__select" x-on:change="show($event.target.value)">
                            @foreach ($previewUrls as $view => $url)
                                <option value="{{ $view }}">{{ __('Error :code', ['code' => $view]) }}</option>
                            @endforeach
                        </select>
                    @endif
                    <div class="page-preview__devices" role="group" aria-label="{{ __('Preview width') }}">
                        <button type="button" class="page-preview__device" x-on:click="device = 'desktop'" x-bind:aria-pressed="(device === 'desktop').toString()">{{ __('Desktop') }}</button>
                        <button type="button" class="page-preview__device" x-on:click="device = 'phone'" x-bind:aria-pressed="(device === 'phone').toString()">{{ __('Phone') }}</button>
                    </div>
                    <button type="button" class="page-preview__device" x-on:click="reload()">{{ __('Reload') }}</button>
                </div>
            </div>
            <div class="page-preview__viewport" x-ref="viewport">
                <iframe x-ref="frame" src="{{ reset($previewUrls) }}" title="{{ __('Preview of :page with the draft', ['page' => __($definition['title'])]) }}" class="page-preview__frame" x-bind:style="frameStyle" x-on:load="loading = false" loading="lazy" inert></iframe>
            </div>
            <p class="page-preview__note">{{ __('The preview shows what is saved. Changes appear here a moment after each autosave. To read or use the page itself, open Preview in the save bar: it opens the same page in a new tab.') }}</p>
        </aside>
    </div>

    <x-admin.revisions :editor="$editor" :show="$showHistory" />
</div>
