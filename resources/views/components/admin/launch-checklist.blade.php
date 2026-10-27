{{--
    The "Before launch" checklist: progress (done of total, also as a hairline), the checks still to do, then the finished ones folded away; each check
    with a tick or an open circle (never colour alone), a sentence on where it stands, and a link
    to the screen that fixes it. Computed from the database by LaunchReadinessService.
--}}
@props(['checklist'])
@php
    $percent = $checklist['total'] === 0 ? 100 : (int) round($checklist['done'] / $checklist['total'] * 100);
    $todo = array_values(array_filter($checklist['checks'], fn (array $check): bool => ! $check['done']));
    $done = array_values(array_filter($checklist['checks'], fn (array $check): bool => $check['done']));
@endphp
<div {{ $attributes->class(['launch-checklist']) }} data-checklist>
    <p class="launch-checklist__progress">
        <span class="font-medium">{{ __(':done of :total ready', ['done' => app(\App\Services\LocaleService::class)->digits($checklist['done']), 'total' => app(\App\Services\LocaleService::class)->digits($checklist['total'])]) }}</span>
        <span class="launch-checklist__track" aria-hidden="true"><span class="launch-checklist__bar" style="--progress: {{ $percent }}%"></span></span>
    </p>
    @if ($todo !== [])
        <ol class="launch-checklist__list">
            @foreach ($todo as $check)
            <li class="launch-checklist__item" data-check="{{ $check['key'] }}" data-done="{{ $check['done'] ? 'true' : 'false' }}">
                <span class="launch-checklist__mark">
                    @if ($check['done'])
                        <x-icon name="check" class="size-4" :label="__('Done')" />
                    @else
                        <x-icon name="minus" class="size-4" :label="__('To do')" />
                    @endif
                </span>
                <div class="launch-checklist__text">
                    <p class="font-medium text-ink">{{ $check['label'] }}</p>
                    <p class="text-sm font-normal text-ink-muted">{{ $check['detail'] }}</p>
                </div>
                @if (! $check['done'] && $check['url'])
                    <a href="{{ $check['url'] }}" class="launch-checklist__fix">{{ $check['action'] }}<span class="sr-only">: {{ $check['label'] }}</span></a>
                @endif
            </li>
            @endforeach
        </ol>
    @endif
    @if ($done !== [])
        <details class="launch-checklist__done" @if ($todo === []) open @endif>
            <summary>{{ trans_choice('{1} :count already done|[2,*] :count already done', count($done), ['count' => app(\App\Services\LocaleService::class)->digits(count($done))]) }}</summary>
            <ol class="launch-checklist__list">
                @foreach ($done as $check)
            <li class="launch-checklist__item" data-check="{{ $check['key'] }}" data-done="{{ $check['done'] ? 'true' : 'false' }}">
                <span class="launch-checklist__mark">
                    @if ($check['done'])
                        <x-icon name="check" class="size-4" :label="__('Done')" />
                    @else
                        <x-icon name="minus" class="size-4" :label="__('To do')" />
                    @endif
                </span>
                <div class="launch-checklist__text">
                    <p class="font-medium text-ink">{{ $check['label'] }}</p>
                    <p class="text-sm font-normal text-ink-muted">{{ $check['detail'] }}</p>
                </div>
                @if (! $check['done'] && $check['url'])
                    <a href="{{ $check['url'] }}" class="launch-checklist__fix">{{ $check['action'] }}<span class="sr-only">: {{ $check['label'] }}</span></a>
                @endif
            </li>
                @endforeach
            </ol>
        </details>
    @endif
</div>
