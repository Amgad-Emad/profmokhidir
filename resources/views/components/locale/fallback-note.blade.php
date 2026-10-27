{{--
    On an Arabic page whose content has no Arabic version yet: a small note that it is shown in English.
    Pass the content model (any App\Models\Concerns\HasTranslatableContent model), or `show` outright.
    The English content itself should carry lang="en" dir="ltr" (see Locales helpers in the views).
--}}
@props(['model' => null, 'show' => null])
@php
    $show ??= $model !== null && method_exists($model, 'showsFallback') && $model->showsFallback();
@endphp
@if ($show)
    <p {{ $attributes->class(['locale-fallback-note']) }} role="note" data-locale-fallback>
        <x-icon name="info" class="size-4 shrink-0" />
        <span>{{ __('This content is available in English only.') }}</span>
    </p>
@endif
