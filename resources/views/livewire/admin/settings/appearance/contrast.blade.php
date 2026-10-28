{{-- The contrast guard: every pair the design draws, checked live in both modes against WCAG 2.2 AA, failures first with a Fix. --}}
@use('App\Constants\Theme')
@php
    $total = count($checks);
    $passing = $total - count($failures);
    $blocking = count(array_filter($failures, static fn (array $failure): bool => $failure['critical']));
@endphp
<section class="contrast-guard" aria-labelledby="contrast-guard-title" data-contrast-guard>
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 id="contrast-guard-title" class="t-label text-accent-text">{{ __('Contrast check') }}</h3>
        <p class="text-sm font-normal text-ink-muted" role="status" data-contrast-summary>
            {{ __(':passing of :total pairs pass in both modes', ['passing' => $passing, 'total' => $total]) }}@if ($blocking > 0) · <strong class="font-medium text-danger">{{ trans_choice('{1} 1 blocks publishing|[2,*] :count block publishing', $blocking, ['count' => $blocking]) }}</strong>@endif
        </p>
    </div>
    <p class="mt-2 text-sm font-normal text-ink-muted">{{ __('Text needs 4.5:1 against what is behind it; large text, rules, focus rings and chart lines 3:1. Body text, links and buttons must pass before a theme can be published.') }}</p>

    @if ($failures === [])
        <x-alert tone="cypress" class="mt-4" data-contrast-ok>{{ __('Every pair passes in the light and the dark mode.') }}</x-alert>
    @else
        <ul class="contrast-list mt-4" role="list" data-contrast-failures>
            @foreach ($failures as $failure)
                <li class="contrast-issue" wire:key="contrast-{{ $failure['mode'] }}-{{ $failure['id'] }}" data-contrast-failure="{{ $failure['mode'] }}.{{ $failure['id'] }}" @if ($failure['critical']) data-blocking @endif>
                    <span class="contrast-issue__sample" style="--sample-ink: {{ $failure['foreground'] }}; --sample-surface: {{ $failure['background'] }}" aria-hidden="true"><span class="contrast-issue__ink"></span></span>
                    <div class="min-w-0">
                        <p class="font-medium text-ink">{{ __($failure['label']) }} <span class="font-normal text-ink-muted">· {{ Theme::from($failure['mode'])->label() }}</span></p>
                        <p class="text-sm font-normal text-ink-muted">
                            {{ __(':ratio:1, needs :minimum:1', ['ratio' => number_format($failure['ratio'], 2), 'minimum' => number_format($failure['minimum'], 1)]) }}
                            @if ($failure['critical'])
                                · <span class="font-medium text-danger"><x-icon name="warning" class="size-4 align-[-0.2em]" /> {{ __('Blocks publishing') }}</span>
                            @endif
                        </p>
                    </div>
                    @if ($editable && $failure['suggestion'] !== null)
                        <x-button type="button" size="sm" wire:click="fix('{{ $failure['mode'] }}', '{{ $failure['id'] }}')" data-fix="{{ $failure['mode'] }}.{{ $failure['id'] }}">
                            <span class="token-row__chip" style="--swatch: {{ $failure['suggestion'] }}" aria-hidden="true"></span>{{ __('Fix') }}<span class="sr-only"> {{ __(':label in :mode: use :colour', ['label' => __($failure['label']), 'mode' => Theme::from($failure['mode'])->label(), 'colour' => $failure['suggestion']]) }}</span>
                        </x-button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
