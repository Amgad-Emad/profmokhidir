{{--
    Admin › Appearance (App\Livewire\Admin\Settings\Appearance): the brand note and "Restore brand
    colours", the suggested templates and custom themes, then the editor of the selected theme beside a
    live preview of real pages. Every colour comes from the theme being edited; nothing here names one.
    With editing off (config appearance.editing, the default) every theme can be inspected and applied,
    but the controls that change colours or make a new theme are not shown ($editing, $editable).
--}}
@use('App\Constants\Appearance')
<div class="appearance" data-appearance>
    <x-admin.page-header :eyebrow="__('Settings')" :title="__('Appearance')" :intro="$editing ? __('Choose the colours of the whole site, the community chat, the sign-in screens, the dashboard and the accent of emails, separately for the light and the dark mode. Fonts and layout stay as they are.') : __('Choose which colour theme the whole site, the community chat, the sign-in screens, the dashboard and emails use. Inspect any theme in the light and the dark mode before you apply it. Themes cannot be edited or added here.')">
        <x-button type="button" :variant="$active?->slug === \App\Constants\ThemePresets::BRAND ? 'secondary' : 'primary'" wire:click="restoreBrand" data-restore-brand>{{ __('Restore brand colours') }}</x-button>
    </x-admin.page-header>

    <x-alert class="mt-8 max-w-3xl" :title="__('About the brand guidelines')" data-brand-note>{{ __(Appearance::BRAND_NOTE) }}</x-alert>

    @include('livewire.admin.settings.appearance.templates')

    <section class="appearance-workspace" aria-labelledby="appearance-editor-title" data-appearance-editor>
        <div class="appearance-workspace__editor">
            @include('livewire.admin.settings.appearance.editor')
        </div>
        <div class="appearance-workspace__preview">
            @include('livewire.admin.settings.appearance.preview')
        </div>
    </section>

    @if ($confirming === \App\Livewire\Admin\Settings\Appearance::CONFIRM_SAVE)
        <x-admin.confirm-dialog :show="true" :title="__('Save with contrast failures?')" action="saveDraft(true)" :confirm-label="__('Save anyway')" data-save-anyway>
            {{ trans_choice('{1} One colour pair fails WCAG 2.2 AA, so some text or controls will be hard to see.|[2,*] :count colour pairs fail WCAG 2.2 AA, so some text or controls will be hard to see.', count($failures), ['count' => count($failures)]) }}
            {{ __('The draft is kept and the live site does not change; fix the pairs before you publish.') }}
        </x-admin.confirm-dialog>
    @elseif ($confirming === \App\Livewire\Admin\Settings\Appearance::CONFIRM_PUBLISH)
        <x-admin.confirm-dialog :show="true" :title="__('Publish with contrast failures?')" action="publish(true)" :confirm-label="__('Publish anyway')" data-publish-anyway>
            {{ trans_choice('{1} One colour pair fails WCAG 2.2 AA.|[2,*] :count colour pairs fail WCAG 2.2 AA.', count($failures), ['count' => count($failures)]) }}
            {{ __('Body text, links and buttons pass, so the theme can go live, but some secondary text or rules will be hard to see.') }}
        </x-admin.confirm-dialog>
    @elseif ($confirming === \App\Livewire\Admin\Settings\Appearance::CONFIRM_DELETE)
        <x-admin.confirm-dialog :show="true" danger :title="__('Delete “:name”?', ['name' => $theme->name])" action="delete" :confirm-label="__('Delete theme')" data-delete-theme-dialog>
            {{ __('The theme and its version history are deleted for good.') }}
            @if ($theme->is_active)
                {{ __('It is live now, so the site returns to Evergreen & Ivory.') }}
            @endif
        </x-admin.confirm-dialog>
    @endif

    <x-admin.drawer :show="$showHistory" :title="__('Version history')" close="toggleHistory" id="theme-history" :description="__('Each save and publish keeps a copy. Load one to make it the draft again.')">
        @if ($revisions->isEmpty())
            <x-empty-state :message="__('No earlier versions yet.')" class="py-8" />
        @else
            <ol class="revision-list" data-revisions>
                @foreach ($revisions as $revision)
                    <li class="revision-list__item" wire:key="theme-revision-{{ $revision->getKey() }}">
                        <div>
                            <p class="font-medium text-ink">{{ $revision->kind->label() }}{{ $loop->first ? ' · '.__('latest') : '' }}</p>
                            <p class="text-sm font-normal text-ink-muted">
                                <time datetime="{{ $revision->created_at->toIso8601String() }}">{{ $revision->created_at->translatedFormat(\App\Constants\DateFormats::LONG.', H:i') }}</time>{{ $revision->user ? ' · '.$revision->user->name : '' }}
                            </p>
                        </div>
                        @if (! $loop->first && $editing)
                            <x-button variant="quiet" size="sm" type="button" wire:click="restoreRevision({{ $revision->getKey() }})" data-restore-revision="{{ $revision->getKey() }}">{{ __('Load this version') }}</x-button>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </x-admin.drawer>
</div>
