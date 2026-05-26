<div class="welcome-popup"
     id="footer-promo-popup"
     data-footer-promo-popup
     hidden
     aria-hidden="true">
    <div class="welcome-popup__backdrop" data-footer-promo-close tabindex="-1"></div>
    <div class="welcome-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="footer-promo-title">
        <button type="button" class="welcome-popup__close" data-footer-promo-close aria-label="Close">&times;</button>
        <div class="welcome-popup__card welcome-popup__card--compact">
            <div class="welcome-popup__content">
                <h2 id="footer-promo-title" class="welcome-popup__title">Get 10% off your first order</h2>
                <p class="footer-promo-popup__lede">Sign up with your email and we will send you a voucher code to use at checkout.</p>
                <form class="welcome-popup__form" method="post" action="{{ route('shop.promo.signup') }}" data-footer-promo-form>
                    @csrf
                    <label class="sr-only" for="footer-promo-email">Email</label>
                    <input id="footer-promo-email"
                           class="welcome-popup__input"
                           type="email"
                           name="email"
                           required
                           autocomplete="email"
                           placeholder="Enter your email">
                    <button class="welcome-popup__submit" type="submit">Send my code</button>
                </form>
                <p class="welcome-popup__success" data-footer-promo-success hidden role="status">Thank you — check your inbox for your 10% off code.</p>
            </div>
        </div>
    </div>
</div>
