{{--
    "English | العربية" tabs around the translatable fields of an edit screen (the ARIA tabs
    pattern: one Tab stop on the selected tab, arrow keys, Home and End move between them). Each
    language is an <x-admin.locale-panel tabs="{id}" locale="..."> in the slot, written in its own
    language and direction (English stays left-to-right even when the dashboard is in Arabic).
    `fields` names the ContentForm fields inside (or `error-keys` lists the full error keys per
    language, for forms bound elsewhere), so a tab whose fields have errors is marked, and a link
    in the error summary to a field in the hidden panel opens that panel first. Fields that are
    the same in every language (years, links, files) stay outside the tabs.
--}}
@props(['id', 'label', 'fields' => [], 'errorKeys' => null])
@use('App\Constants\Locales')
@use('App\Livewire\Admin\ContentForm')
@php
    $invalid = [];
    $bag = $errors ?? null;

    foreach (Locales::all() as $locale) {
        $prefix = $locale === Locales::DEFAULT ? ContentForm::FIELD_PREFIX : ContentForm::FIELD_PREFIX.ContentForm::TRANSLATIONS.'.'.$locale.'.';
        $keys = $errorKeys === null ? array_map(fn (string $field): string => $prefix.$field, $fields) : ($errorKeys[$locale] ?? []);

        foreach ($keys as $key) {
            if ($bag !== null && ($bag->has($key) || $bag->has($key.'.*'))) {
                $invalid[$locale] = true;
            }
        }
    }
@endphp
<div {{ $attributes->class(['locale-tabs grid gap-6']) }} data-locale-tabs="{{ $id }}"
     x-data="{
        active: @js(Locales::DEFAULT),
        order: @js(Locales::all()),
        select(locale, focus) {
            this.active = locale;
            if (focus) this.$nextTick(() => this.$refs['tab_' + locale].focus());
        },
        key(event) {
            let index = this.order.indexOf(this.active);
            const rtl = getComputedStyle(this.$el).direction === 'rtl';
            if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
                index += (event.key === 'ArrowRight') !== rtl ? 1 : -1;
            } else if (event.key === 'Home') {
                index = 0;
            } else if (event.key === 'End') {
                index = this.order.length - 1;
            } else {
                return;
            }
            event.preventDefault();
            this.select(this.order[(index + this.order.length) % this.order.length], true);
        },
        reveal(event) {
            const link = event.target.closest('a');
            const href = link ? link.getAttribute('href') || '' : '';
            if (! href.startsWith('#') || href.length < 2) return;
            const target = document.getElementById(decodeURIComponent(href.slice(1)));
            const panel = target ? target.closest('[data-locale-panel]') : null;
            if (! panel || ! this.$el.contains(panel) || panel.dataset.localePanel === this.active) return;
            event.preventDefault();
            this.active = panel.dataset.localePanel;
            this.$nextTick(() => target.focus());
        },
     }"
     x-on:click.window="reveal($event)">
    <div class="flex flex-wrap items-center gap-2 border-b border-rule" role="tablist" aria-label="{{ __('Language of :group', ['group' => $label]) }}" x-on:keydown="key($event)">
        @foreach (Locales::all() as $locale)
            @php $selected = $locale === Locales::DEFAULT; @endphp
            <button type="button" role="tab" class="admin-tab inline-flex items-center gap-2" id="{{ $id }}-tab-{{ $locale }}" aria-controls="{{ $id }}-panel-{{ $locale }}"
                    lang="{{ Locales::html($locale) }}" dir="{{ Locales::dir($locale) }}"
                    aria-selected="{{ $selected ? 'true' : 'false' }}" tabindex="{{ $selected ? '0' : '-1' }}"
                    x-bind:aria-selected="active === '{{ $locale }}' ? 'true' : 'false'" x-bind:tabindex="active === '{{ $locale }}' ? 0 : -1"
                    x-on:click="select('{{ $locale }}', false)" x-ref="tab_{{ $locale }}" data-locale-tab="{{ $locale }}"
                    @if (isset($invalid[$locale])) data-invalid @endif>
                {{ Locales::native($locale) }}
                @if (isset($invalid[$locale]))
                    <x-icon name="warning" class="size-4 text-danger" /><span class="sr-only" lang="{{ app()->getLocale() }}">{{ __('(has errors)') }}</span>
                @endif
            </button>
        @endforeach
    </div>
    {{ $slot }}
</div>
