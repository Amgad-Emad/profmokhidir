{{--
    The edit screen: the form split into named sections (<x-admin.form-section>), a sticky "On this
    page" section nav at the start side on wide screens (when there are two or more), the placeholder
    notice, and the sticky save bar (<x-admin.save-bar>) along the bottom. `after` holds sections
    that save on their own (photographs, the portrait), outside the form but in the same nav.
    Alpine `editScreen` (resources/js/admin/editing.js) tracks changes and autosaves drafts.
    `editor` comes from ContentForm::editorState(). `autosave` overrides the default (drafts only):
    the page editor keeps drafts beside the published page, so it autosaves either way. `draftable`
    and `publish-label` pass through to the save bar.
--}}
@props(['editor', 'sections' => [], 'saveLabel' => null, 'autosave' => null, 'draftable' => false, 'publishLabel' => null, 'saveOnly' => false])
@use('App\Constants\AdminUx')
@use('App\Constants\Placeholders')
@php
    $autosave ??= $editor['status'] !== \App\Constants\ContentStatus::Published;
    // Only fixed values go in x-data: a changed expression would restart the component on every save.
    $config = ['seconds' => AdminUx::AUTOSAVE_SECONDS];
@endphp
<div {{ $attributes->class(['edit-layout', 'edit-layout--nav' => count($sections) > 1]) }} x-data="editScreen({{ \Illuminate\Support\Js::from($config) }})" data-edit-screen
     data-autosave="{{ $autosave ? 'true' : 'false' }}" data-saved-at="{{ $editor['savedAt'] }}">
    @if (count($sections) > 1)
        <nav class="edit-layout__nav" aria-label="{{ __('Sections of this form') }}" x-data="sectionNav">
            <p class="t-label text-accent-text">{{ __('On this page') }}</p>
            <ol class="edit-layout__links">
                @foreach ($sections as $id => $label)
                    <li><a href="#section-{{ $id }}" class="edit-layout__link" x-bind:aria-current="current === '{{ $id }}' ? 'true' : null" data-section-link="{{ $id }}">{{ $label }}</a></li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="edit-layout__body">
        @if ($editor['placeholders'] !== [])
            <x-alert tone="bronze" class="mb-8" data-placeholder-summary>
                {{ trans_choice('{1} One field on this page still holds sample text from the site set-up. It is marked “:flag”.|[2,*] :count fields on this page still hold sample text from the site set-up. They are marked “:flag”.', count($editor['placeholders']), ['flag' => __(Placeholders::FLAG)]) }}
            </x-alert>
        @endif

        <x-form.error-summary after-save :intro="__(\App\Constants\FormControls::ERROR_SUMMARY_INTRO)" />
        <div class="edit-layout__sections">{{ $slot }}</div>

        {{ $after ?? '' }}
    </div>

    <x-admin.save-bar :editor="$editor" :save-label="$saveLabel" :draftable="$draftable" :publish-label="$publishLabel" :save-only="$saveOnly" />
</div>
