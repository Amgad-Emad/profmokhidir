{{-- The suggested templates (read-only: Apply or Duplicate to edit), then the custom themes and Import. With editing off: Apply and Inspect only. --}}
@use('App\Constants\Appearance')
@use('App\Constants\ThemePresets')
@php
    $presetThemes = $themes->where('is_preset', true);
    $brandTheme = $presetThemes->firstWhere('slug', ThemePresets::BRAND);
    $alternatives = $presetThemes->where('slug', '!=', ThemePresets::BRAND);
    $custom = $themes->where('is_preset', false);
@endphp
<section class="mt-12" aria-labelledby="appearance-templates-title" data-appearance-templates>
    <h2 id="appearance-templates-title" class="font-display text-3xl font-light">{{ __('Suggested templates') }}</h2>
    <p class="mt-2 max-w-2xl text-sm font-normal text-ink-muted">{{ $editing ? __('Each has a light and a dark variant that pass the contrast check. Templates cannot be changed: apply one as it is, or duplicate it to make your own.') : __('Each has a light and a dark variant that pass the contrast check. Inspect one to see all its colours, or apply it as it is.') }}</p>

    <ul class="theme-cards mt-6" role="list">
        @foreach ([$brandTheme, ...$alternatives->all()] as $preset)
            @if ($loop->index === 1)
                <li class="theme-cards__divider"><p class="t-label text-accent-text">{{ __(Appearance::ALTERNATIVES_LABEL) }}</p></li>
            @endif
            @php($definition = ThemePresets::get($preset->slug))
            <li @class(['theme-card card', 'theme-card--brand' => $preset->slug === ThemePresets::BRAND]) wire:key="preset-{{ $preset->id }}" data-preset="{{ $preset->slug }}">
                <div class="theme-card__minis">
                    @include('livewire.admin.settings.appearance.mini', ['colours' => $presetColours[$preset->id], 'mode' => 'light'])
                    @include('livewire.admin.settings.appearance.mini', ['colours' => $presetColours[$preset->id], 'mode' => 'dark'])
                </div>
                <div class="theme-card__text">
                    <h3 class="theme-card__name">{{ __($definition['name']) }}</h3>
                    @if ($preset->slug === ThemePresets::BRAND)
                        <p class="t-label text-accent-text" data-recommended>{{ __(Appearance::RECOMMENDED_LABEL) }}</p>
                    @endif
                    <p class="text-sm font-normal text-ink-muted">{{ __($definition['description']) }}</p>
                    @if ($preset->is_active)
                        <x-admin.status-badge tone="cypress" :label="__('Live')" class="mt-2 self-start" data-live />
                    @endif
                </div>
                <div class="theme-card__actions">
                    <x-button type="button" size="sm" :variant="$preset->is_active ? 'secondary' : 'primary'" wire:click="apply({{ $preset->id }})" :disabled="$preset->is_active" data-apply="{{ $preset->slug }}">{{ $preset->is_active ? __('Applied') : __('Apply') }}<span class="sr-only"> {{ __($definition['name']) }}</span></x-button>
                    @if ($editing)
                        <x-button type="button" size="sm" variant="quiet" wire:click="duplicate({{ $preset->id }})" data-duplicate="{{ $preset->slug }}">{{ __('Duplicate to edit') }}<span class="sr-only"> {{ __($definition['name']) }}</span></x-button>
                    @endif
                    <x-button type="button" size="sm" variant="quiet" wire:click="select({{ $preset->id }})" :aria-pressed="$theme->id === $preset->id ? 'true' : 'false'" data-inspect="{{ $preset->slug }}">{{ __('Inspect') }}<span class="sr-only"> {{ __($definition['name']) }}</span></x-button>
                </div>
            </li>
        @endforeach
    </ul>

    @if ($editing || $custom->isNotEmpty())
    <div class="mt-12 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="font-display text-3xl font-light">{{ __('Your themes') }}</h2>
            @if ($editing)
                <p class="mt-2 text-sm font-normal text-ink-muted">{{ __('Duplicate a template to start one, or import a theme exported from this screen.') }}</p>
            @endif
        </div>
        @if ($editing)
        <div class="flex flex-col items-end gap-1">
            <label class="btn btn--secondary btn--small cursor-pointer" for="theme-import">
                <x-icon name="upload" class="size-4" />{{ __('Import JSON') }}
            </label>
            <input id="theme-import" type="file" class="sr-only" accept="application/json,.json" wire:model="importFile" aria-describedby="theme-import-error" data-import>
            <p id="theme-import-error" class="form-error max-w-sm text-sm" role="alert">@error('importFile'){{ $message }}@enderror</p>
        </div>
        @endif
    </div>

    @if ($custom->isEmpty())
        <x-empty-state class="mt-6" :message="__('No themes of your own yet. Duplicate a template above to make one.')" data-themes-empty />
    @else
        <ul class="theme-list mt-6" role="list" data-custom-themes>
            @foreach ($custom as $own)
                <li class="theme-list__item" wire:key="theme-{{ $own->id }}">
                    <span class="theme-list__name">{{ $own->name }}</span>
                    @if ($own->is_active)
                        <x-admin.status-badge tone="cypress" :label="__('Live')" />
                    @endif
                    @if ($own->hasDraft())
                        <x-admin.status-badge tone="bronze" :label="__('Draft')" />
                    @endif
                    @if ($editing)
                        <x-button type="button" size="sm" variant="quiet" wire:click="select({{ $own->id }})" :aria-pressed="$theme->id === $own->id ? 'true' : 'false'" data-edit-theme="{{ $own->id }}"><x-icon name="pencil" class="size-4" />{{ __('Edit') }}<span class="sr-only"> {{ $own->name }}</span></x-button>
                    @else
                        <x-button type="button" size="sm" variant="quiet" wire:click="select({{ $own->id }})" :aria-pressed="$theme->id === $own->id ? 'true' : 'false'" data-inspect-theme="{{ $own->id }}">{{ __('Inspect') }}<span class="sr-only"> {{ $own->name }}</span></x-button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    @endif
</section>
