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
    @case('heading')
        <path d="M5 5v14" /><path d="M14 5v14" /><path d="M5 12h9" /><path d="M17.5 12.5l2-1.5v8" />
        @break
    @case('subheading')
        <path d="M5 7v10" /><path d="M12 7v10" /><path d="M5 12h7" /><path d="M16 13.25a1.75 1.75 0 1 1 3.5 0c0 1.6-3.5 2.6-3.5 4.75h3.75" />
        @break
    @case('bold')
        <path d="M7 5h6a3.5 3.5 0 0 1 0 7H7z" /><path d="M7 12h7a3.5 3.5 0 0 1 0 7H7z" />
        @break
    @case('italic')
        <path d="M10 5h8" /><path d="M6 19h8" /><path d="M14.5 5l-5 14" />
        @break
    @case('list-bullet')
        <path d="M9.5 7H20" /><path d="M9.5 12H20" /><path d="M9.5 17H20" /><circle cx="5" cy="7" r="0.75" fill="currentColor" /><circle cx="5" cy="12" r="0.75" fill="currentColor" /><circle cx="5" cy="17" r="0.75" fill="currentColor" />
        @break
    @case('list-numbered')
        <path d="M10 7h10" /><path d="M10 12h10" /><path d="M10 17h10" /><path d="M4.5 5.5l1.25-.75v4" /><path d="M4.25 14.25a1.25 1.25 0 1 1 2.25.75L4.25 18H6.75" />
        @break
    @case('quote')
        <path d="M6 18c2.5-1.25 3.5-3.25 3.5-6V7H5v5h4.5" /><path d="M15 18c2.5-1.25 3.5-3.25 3.5-6V7H14v5h4.5" />
        @break
    @case('undo')
        <path d="M9 7.5L5 11.5l4 4" /><path d="M5 11.5h9.5a4.5 4.5 0 0 1 0 9H12" />
        @break
    @case('redo')
        <path d="M15 7.5l4 4-4 4" /><path d="M19 11.5H9.5a4.5 4.5 0 0 0 0 9H12" />
        @break
    @case('upload')
        <path d="M12 15.5V4.5" /><path d="M7.5 9L12 4.5 16.5 9" /><path d="M4.5 15v3.5a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1V15" />
        @break
    @case('image')
        <rect x="3.5" y="5" width="17" height="14" rx="1" /><circle cx="9" cy="10" r="1.5" /><path d="M20.5 16l-5-4.5-8 7.5" />
        @break
    @case('file')
        <path d="M13.5 3.5H7a1 1 0 0 0-1 1v15a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V8z" /><path d="M13.5 3.5V8H18" />
        @break
    @case('chevron-down')
        <path d="M6 9.5l6 6 6-6" />
        @break
    @case('plus')
        <path d="M12 5v14" /><path d="M5 12h14" />
        @break
    @case('home')
        <path d="M4 11l8-6.5 8 6.5" /><path d="M6 9.5V19.5h12V9.5" /><path d="M10 19.5v-5h4v5" />
        @break
    @case('book')
        <path d="M5 5.5A1.5 1.5 0 0 1 6.5 4H19v13H6.5A1.5 1.5 0 0 0 5 18.5z" /><path d="M5 18.5A1.5 1.5 0 0 0 6.5 20H19" />
        @break
    @case('award')
        <circle cx="12" cy="9" r="5" /><path d="M9 13.2L7.5 20l4.5-2.5 4.5 2.5L15 13.2" />
        @break
    @case('feather')
        <path d="M19 5c-6 0-11 5-11 11v3" /><path d="M8 16c5 0 9-3 10.5-8" /><path d="M8 12h6" />
        @break
    @case('layers')
        <path d="M12 4.5l8 4-8 4-8-4z" /><path d="M4 12.5l8 4 8-4" /><path d="M4 16.5l8 4 8-4" />
        @break
    @case('video')
        <rect x="3.75" y="6.75" width="12" height="10.5" rx="1" /><path d="M15.75 10.5l4.5-2.5v8l-4.5-2.5" />
        @break
    @case('grid')
        <rect x="4.5" y="4.5" width="6" height="6" rx="0.5" /><rect x="13.5" y="4.5" width="6" height="6" rx="0.5" /><rect x="4.5" y="13.5" width="6" height="6" rx="0.5" /><rect x="13.5" y="13.5" width="6" height="6" rx="0.5" />
        @break
    @case('users')
        <circle cx="9" cy="8.5" r="3.25" /><path d="M3.5 19c.6-3 2.8-4.75 5.5-4.75s4.9 1.75 5.5 4.75" /><path d="M15 5.5a3.25 3.25 0 0 1 0 6.25" /><path d="M17 14.5c1.9.5 3.1 2 3.5 4.5" />
        @break
    @case('chat')
        <path d="M4.5 6.5A1.5 1.5 0 0 1 6 5h12a1.5 1.5 0 0 1 1.5 1.5v8A1.5 1.5 0 0 1 18 16H10l-4.5 3.5V16H6a1.5 1.5 0 0 1-1.5-1.5z" />
        @break
    @case('heart')
        <path d="M12 19.5s-7.5-4.4-7.5-9.75A4.25 4.25 0 0 1 12 7.2a4.25 4.25 0 0 1 7.5 2.55C19.5 15.1 12 19.5 12 19.5z" />
        @break
    @case('settings')
        <circle cx="12" cy="12" r="2.75" /><path d="M12 3.75v2.5M12 17.75v2.5M3.75 12h2.5M17.75 12h2.5M6.2 6.2l1.75 1.75M16.05 16.05l1.75 1.75M6.2 17.8l1.75-1.75M16.05 7.95l1.75-1.75" />
        @break
    @case('external')
        <path d="M13.5 4.5h6v6" /><path d="M19.5 4.5L11 13" /><path d="M17.5 13.5v5a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1h5" />
        @break
    @case('history')
        <path d="M4.5 12a7.5 7.5 0 1 0 2.2-5.3" /><path d="M4.5 4.5v3.75h3.75" /><path d="M12 8v4.25l2.75 1.75" />
        @break
    @case('more')
        <path d="M6 12h.01" /><path d="M12 12h.01" /><path d="M18 12h.01" />
        @break
    @case('trash')
        <path d="M5 7h14" /><path d="M9.5 7V5h5v2" /><path d="M7 7l.8 12h8.4L17 7" />
        @break
    @case('keyboard')
        <rect x="3.5" y="6.5" width="17" height="11" rx="1" /><path d="M7 10h.01M10 10h.01M13 10h.01M16 10h.01M8 14h8" />
        @break
    @case('collapse')
        <rect x="3.75" y="4.75" width="16.5" height="14.5" rx="1" /><path d="M9 4.75v14.5" /><path d="M15 10l-2 2 2 2" />
        @break
    @case('expand')
        <rect x="3.75" y="4.75" width="16.5" height="14.5" rx="1" /><path d="M9 4.75v14.5" /><path d="M13 10l2 2-2 2" />
        @break
    @default
        <circle cx="12" cy="12" r="8.25" />
@endswitch
</svg>
