<div class="footer-newsletter" id="footer-newsletter" data-footer-newsletter>
    <h2 class="footer-newsletter__title">Sign up for <span class="promo-numeric">10%</span> off</h2>
    <form class="footer-newsletter__form"
          method="post"
          action="{{ route('shop.promo.signup') }}"
          data-footer-promo-form
          novalidate>
        @csrf
        <div class="footer-newsletter__field">
            <label class="sr-only" for="footer-newsletter-email">Email address</label>
            <input id="footer-newsletter-email"
                   class="footer-newsletter__input"
                   type="email"
                   name="email"
                   required
                   autocomplete="email"
                   placeholder="Enter your email address">
            <button class="footer-newsletter__submit" type="submit" aria-label="Subscribe">
                <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
        <label class="footer-newsletter__consent">
            <input type="checkbox" name="promo_consent" value="1" required>
            <span>By signing up, you agree to our <a href="{{ route('shop.policy.security') }}">Security</a> &amp; <a href="{{ route('shop.policy.privacy') }}">Privacy</a>.</span>
        </label>
    </form>
    <p class="footer-newsletter__message" data-footer-promo-message hidden role="status"></p>
</div>
