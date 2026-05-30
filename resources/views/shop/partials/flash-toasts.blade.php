@php
    $flashMessages = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error')],
    ])->filter(fn ($flash) => filled($flash['message']));
@endphp

@if($flashMessages->isNotEmpty())
    <div class="toast-stack" data-toast-stack aria-live="polite">
        @foreach($flashMessages as $flash)
            <div
                class="toast toast--{{ $flash['type'] }}"
                data-toast
                role="{{ $flash['type'] === 'error' ? 'alert' : 'status' }}"
            >
                <span class="toast__icon" aria-hidden="true">
                    @if($flash['type'] === 'success')
                        <svg viewBox="0 0 24 24" focusable="false"><path d="M20 6 9 17l-5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 8v5M12 16h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    @endif
                </span>
                <p class="toast__message">{{ $flash['message'] }}</p>
                <button type="button" class="toast__close" data-toast-close aria-label="Dismiss notification">
                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
