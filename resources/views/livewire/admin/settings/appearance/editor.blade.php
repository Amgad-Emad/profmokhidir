{{-- The editor of the selected theme: name, actions, the Light | Dark switch, the contrast guard, the Basic colours and the Advanced colours. --}}
@use('App\Constants\Theme')
@use('App\Constants\ThemeTokens')
@php
    $modeLabel = Theme::from($mode)->label();
    $explicit = $mode === 'light' ? $light : $dark;
    $templateMode = $template[$mode];
    $advancedCount = count(array_filter(array_keys($explicit), static fn (string $token): bool => ! in_array($token, ThemeTokens::basic(), true)));
@endphp
<div class="flex flex-col gap-10" x-data="appearanceEditor">
    <header class="flex flex-col gap-4">
        <p class="t-label text-accent-text" data-theme-kind>
            {{ $theme->is_preset ? __('Template') : __('Your theme') }}
            · {{ $theme->is_active ? __('Live') : ($theme->hasDraft() ? __('Draft saved') : __('Not live')) }}
            @if ($dirty)
                · <span data-unsaved>{{ __('Unsaved changes') }}</span>
            @endif
        </p>
        <h2 id="appearance-editor-title" class="font-display text-4xl font-light">{{ $theme->name }}</h2>
        @if (! $editing)
            <p class="max-w-xl text-sm font-normal text-ink-muted" data-read-only>{{ __('Shown read-only: the colours of a theme cannot be changed here. Apply it to use it on the site.') }}</p>
        @elseif (! $editable)
            <p class="max-w-xl text-sm font-normal text-ink-muted" data-read-only>{{ __('Templates are read-only. Duplicate this one to change its colours; the copy keeps a link to it, so any colour can be reset to the template.') }}</p>
        @else
            <form wire:submit="rename" class="form-row max-w-xl" novalidate data-rename>
                <x-form.input class="form-col--two-thirds" model="name" :label="__('Name')" :value="$name" maxlength="{{ \App\Constants\Appearance::NAME_MAX }}" />
                <div class="form-col--action"><x-button type="submit" size="sm">{{ __('Rename') }}</x-button></div>
            </form>
        @endif
    </header>

    <div class="appearance-actions" data-appearance-actions>
        @if ($editable)
            <x-button type="button" wire:click="saveDraft" wire:loading.attr="disabled" data-save-draft>{{ __('Save as draft') }}</x-button>
        @endif
        <x-button type="button" variant="primary" wire:click="publish" wire:loading.attr="disabled" :disabled="$theme->is_preset && $theme->is_active" data-publish>{{ $theme->is_preset || ! $editing ? __('Apply to the site') : __('Publish') }}</x-button>
        <x-button variant="quiet" :href="$previewSiteUrl" target="_blank" rel="noopener" data-preview-site>{{ __('Preview on site') }}<span class="sr-only"> {{ __('(opens in a new tab; shows the last saved draft, to you only)') }}</span></x-button>
        @if ($editing)
            <x-button type="button" variant="quiet" wire:click="duplicate" data-duplicate-current>{{ __('Duplicate') }}</x-button>
        @endif
        <x-button type="button" variant="quiet" wire:click="export" data-export><x-icon name="download" class="size-4" />{{ __('Export JSON') }}</x-button>
        <x-button type="button" variant="quiet" wire:click="toggleHistory" data-history><x-icon name="history" class="size-4" />{{ __('History') }}</x-button>
        @if (! $theme->is_preset && auth()->user()->can('delete', $theme))
            <x-button type="button" variant="danger" wire:click="askDelete" data-delete-theme><x-icon name="trash" class="size-4" />{{ __('Delete') }}</x-button>
        @endif
    </div>

    <div class="appearance-modes" role="group" aria-label="{{ __('Mode being edited') }}" data-mode-switch>
        @foreach (Theme::cases() as $case)
            <x-button type="button" size="sm" :variant="$mode === $case->value ? 'primary' : 'secondary'" wire:click="setMode('{{ $case->value }}')" aria-pressed="{{ $mode === $case->value ? 'true' : 'false' }}" data-mode="{{ $case->value }}">
                <x-icon :name="$case === Theme::Light ? 'theme-light' : 'theme-dark'" class="size-4" />{{ $case->label() }}
            </x-button>
        @endforeach
    </div>

    @include('livewire.admin.settings.appearance.contrast')

    <fieldset class="flex flex-col gap-6" data-basic-colours>
        <legend class="font-display text-3xl font-light">{{ __(ThemeTokens::groups()[ThemeTokens::GROUP_BASIC]['label']) }} <span class="t-label text-accent-text">· {{ $modeLabel }}</span></legend>
        <p class="-mt-2 text-sm font-normal text-ink-muted">{{ __(ThemeTokens::groups()[ThemeTokens::GROUP_BASIC]['intro']) }} {{ __('The other colours follow them unless you set them under Advanced colours.') }}</p>
        <div class="token-grid">
            @foreach (ThemeTokens::basic() as $token)
                @include('livewire.admin.settings.appearance.token-row', ['token' => $token, 'large' => true])
            @endforeach
        </div>
    </fieldset>

    <details class="appearance-advanced" data-advanced>
        <summary class="appearance-advanced__summary">
            <span class="font-display text-3xl font-light">{{ __('Advanced colours') }}</span>
            <span class="text-sm font-normal text-ink-muted">{{ trans_choice('{0} Every one follows the basic colours.|{1} One set by hand.|[2,*] :count set by hand.', $advancedCount, ['count' => $advancedCount]) }}</span>
        </summary>
        <div class="flex flex-col gap-10 pt-6">
            <p class="text-sm font-normal text-ink-muted">{{ __('Every other colour of the site, font colours included. “Follows the basic colours” means it is worked out for you (hover and pressed shades, tints, translucent rules); set one by hand only when you need to.') }}</p>
            @if ($editable)
                <div><x-button type="button" size="sm" variant="quiet" wire:click="resetAdvanced" data-reset-advanced>{{ __('Make every advanced colour follow the basic colours') }}</x-button></div>
            @endif
            @foreach ($groups as $group => $info)
                @continue($group === ThemeTokens::GROUP_BASIC)
                <fieldset class="flex flex-col gap-4" data-token-group="{{ $group }}">
                    <legend class="t-label text-accent-text">{{ __($info['label']) }}</legend>
                    <p class="text-sm font-normal text-ink-muted">{{ __($info['intro']) }}</p>
                    <div class="token-list">
                        @foreach (ThemeTokens::inGroup($group) as $token)
                            @include('livewire.admin.settings.appearance.token-row', ['token' => $token, 'large' => false])
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
        </div>
    </details>
</div>
