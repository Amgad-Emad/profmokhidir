{{-- Small inline line icons (1.5px stroke, currentColor) so state is never shown by colour alone. Decorative by default; pass a `label` to expose it to screen readers. --}}
@props(['name', 'label' => null])
@php $hasSize = preg_match('/\bsize-/', (string) $attributes->get('class')) === 1; @endphp
<svg {{ $attributes->class(['inline-block shrink-0', 'size-5' => ! $hasSize]) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"{!! $label ? ' role="img" aria-label="'.e($label).'"' : ' aria-hidden="true" focusable="false"' !!}>
@switch($name)
    @case('check')
        <path d="M5 12.5l4.5 4.5L19 7.5" />
        @break
    @case('clock')
        <circle cx="12" cy="12" r="8.25" /><path d="M12 7.5V12l3 2" />
        @break
    @case('minus')
        <path d="M6 12h12" />
        @break
    @case('warning')
        <path d="M12 4.5l8.5 15h-17z" /><path d="M12 10v4" /><path d="M12 17.25h.01" />
        @break
    @case('info')
        <circle cx="12" cy="12" r="8.25" /><path d="M12 11v5" /><path d="M12 8h.01" />
        @break
    @case('menu')
        <path d="M4 7h16" /><path d="M4 12h16" /><path d="M4 17h16" />
        @break
    @case('close')
        <path d="M6 6l12 12" /><path d="M18 6L6 18" />
        @break
    @case('arrow')
        <path d="M5 12h14" /><path d="M13 6l6 6-6 6" />
        @break
    @case('shield')
        <path d="M12 3.5l7 2.75v5.25c0 4.5-3 7.75-7 9-4-1.25-7-4.5-7-9V6.25z" /><path d="M9.5 12.25l1.75 1.75 3.5-3.75" />
        @break
    @case('user')
        <circle cx="12" cy="8.5" r="3.5" /><path d="M5 19.5c0-3.5 3.1-5.5 7-5.5s7 2 7 5.5" />
        @break
    @case('lock')
        <rect x="5.5" y="10.5" width="13" height="9" rx="1" /><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5" />
        @break
    @case('eye')
        <path d="M2.5 12s3.5-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.5 6.5-9.5 6.5S2.5 12 2.5 12z" /><circle cx="12" cy="12" r="3" />
        @break
    @case('eye-off')
        <path d="M4 4l16 16" /><path d="M10.6 6.3A9.9 9.9 0 0 1 12 6.2c6 0 9.5 5.8 9.5 5.8a16.5 16.5 0 0 1-3.2 3.9" /><path d="M6.6 8.2A16.3 16.3 0 0 0 2.5 12s3.5 5.8 9.5 5.8a9.7 9.7 0 0 0 3.6-.7" /><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
        @break
    @case('search')
        <circle cx="11" cy="11" r="6.25" /><path d="M15.5 15.5L20 20" />
        @break
    @case('theme-light')
        <circle cx="12" cy="12" r="3.75" /><path d="M12 3v2.25" /><path d="M12 18.75V21" /><path d="M3 12h2.25" /><path d="M18.75 12H21" /><path d="M5.64 5.64l1.6 1.6" /><path d="M16.76 16.76l1.6 1.6" /><path d="M5.64 18.36l1.6-1.6" /><path d="M16.76 7.24l1.6-1.6" />
        @break
    @case('theme-dark')
        <path d="M19.5 14.25A7.75 7.75 0 0 1 9.75 4.5a7.75 7.75 0 1 0 9.75 9.75z" />
        @break
    @case('theme-system')
        <circle cx="12" cy="12" r="8.25" /><path d="M12 3.75v16.5" /><path d="M12 3.75a8.25 8.25 0 0 1 0 16.5z" fill="currentColor" stroke="none" />
        @break
    @case('reply')
        <path d="M9 7l-5 5 5 5" /><path d="M4 12h9a6 6 0 0 1 6 6v1" />
        @break
    @case('link')
        <path d="M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 0 0-5.66-5.66l-1.5 1.5" /><path d="M14 10a4 4 0 0 0-5.66 0l-3 3a4 4 0 0 0 5.66 5.66l1.5-1.5" />
        @break
    @case('pin')
        <path d="M9 4.5h6" /><path d="M10 4.5v5l-3 3v1.5h10V12.5l-3-3v-5" /><path d="M12 14v6" />
        @break
    @case('chevron-left')
        <path d="M14.5 6L8.5 12l6 6" />
        @break
    @case('panel')
        <rect x="3.5" y="5" width="17" height="14" rx="1" /><path d="M15 5v14" />
        @break
    @default
        <circle cx="12" cy="12" r="8.25" />
@endswitch
</svg>
