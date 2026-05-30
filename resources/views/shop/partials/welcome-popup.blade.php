@php($wp = $welcomePopup ?? [])
<div class="welcome-popup"
     id="welcome-popup"
     data-welcome-popup
     data-welcome-delay="{{ (int) ($wp['delay_seconds'] ?? 10) }}"
     hidden
     inert
     aria-hidden="true">
    <div class="welcome-popup__backdrop" data-welcome-close tabindex="-1"></div>
    <div class="welcome-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="welcome-popup-title">
        <button type="button" class="welcome-popup__close" data-welcome-close aria-label="Close welcome offer">&times;</button>
        <div class="welcome-popup__card">
            <div class="welcome-popup__bg" aria-hidden="true" style="background-image: url('{{ $wp['image_url'] ?? asset('assets/img/welcome-popup.png') }}');"></div>
            <div class="welcome-popup__content">
                <h2 id="welcome-popup-title" class="welcome-popup__title">{{ $wp['title'] ?? 'Your 15% Welcome Gift Awaits' }}</h2>
                <form class="welcome-popup__form" method="post" action="{{ route('shop.welcome.offer') }}" data-welcome-form>
                    @csrf
                    <label class="sr-only" for="welcome-popup-email">Email</label>
                    <input id="welcome-popup-email"
                           class="welcome-popup__input"
                           type="email"
                           name="email"
                           required
                           autocomplete="email"
                           placeholder="{{ $wp['email_placeholder'] ?? 'Enter your email' }}">
                    <button class="welcome-popup__submit" type="submit">{{ $wp['submit_label'] ?? 'Reveal My Offer' }}</button>
                </form>
                @if(!empty($wp['legal_html']))
                    <div class="welcome-popup__legal">{!! $wp['legal_html'] !!}</div>
                @endif
                <p class="welcome-popup__success" data-welcome-success hidden role="status">{{ $wp['success_message'] ?? 'Thank you — check your inbox for your welcome offer.' }}</p>
            </div>
        </div>
    </div>
</div>
