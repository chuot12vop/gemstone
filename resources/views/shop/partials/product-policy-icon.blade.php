@php($icon = $icon ?? 'shipping')
<span class="pd-policies__icon" aria-hidden="true">
@switch($icon)
    @case('locker')
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="6" width="20" height="16" rx="1.5"/>
            <path d="M4 11h20M11 6v16M17 6v16M8 15h2M18 15h2"/>
        </svg>
        @break
    @case('warranty')
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="14" cy="14" r="10"/>
            <path d="M9.5 14.2l3.2 3.2 6.8-7"/>
        </svg>
        @break
    @case('returns')
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 10H5.5a2 2 0 0 0-2 2v8.5a2 2 0 0 0 2 2H20a2 2 0 0 0 2-2V12a2 2 0 0 0-2-2h-2.5"/>
            <path d="M11 8.5 8 10.5l3 2"/>
            <path d="M10 14h8"/>
        </svg>
        @break
    @case('gift')
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="12" width="18" height="11" rx="1"/>
            <path d="M14 12V23M5 16h18"/>
            <path d="M14 12c-2.5 0-4-1.2-4-3.2S11.2 6 14 6s4 1.2 4 3.2S16.5 12 14 12z"/>
            <path d="M5 12h18"/>
        </svg>
        @break
    @default
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 10.5 4 12v9.5a1.5 1.5 0 0 0 1.5 1.5H22a1.5 1.5 0 0 0 1.5-1.5V12l-2-1.5"/>
            <path d="M6 10.5h16l-1.5-3H7.5L6 10.5z"/>
            <path d="M10 10.5V8M18 10.5V8"/>
            <path d="M3 12h3M22 12h3"/>
        </svg>
@endswitch
</span>
