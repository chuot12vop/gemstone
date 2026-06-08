# Error 04 - Account, Order, Review

## Scope

Luồng: Login/register/account profile -> order list/detail -> order confirmation -> review.

## Errors

| # | Error | Impact | Suggested UX / QA expectation |
|---|---|---|---|
| 1 | Account chưa có đơn hàng | Empty page | Empty state + CTA Continue shopping |
| 2 | Order list API/query fail | User không xem được lịch sử | Error state + retry |
| 3 | Order number không tồn tại | 404 lạnh | Friendly not found + support link |
| 4 | User xem order của user khác | Data leak | Authorization check + 404/403 |
| 5 | Guest order confirmation bị share link | Privacy risk | Show limited info or require verification |
| 6 | Session expired khi mở account | Redirect login mất context | Login then redirect intended URL |
| 7 | Session expired khi submit profile | Mất form input | Preserve input, reauth modal |
| 8 | Profile email invalid | Validation unclear | Inline email error |
| 9 | Duplicate email profile update | Account conflict | Message email already used |
| 10 | Password current sai | User không biết field nào | Error on current password field |
| 11 | New password yếu | Security fail | Strength hint before submit |
| 12 | Double click save profile | Duplicate requests | Disable save while pending |
| 13 | Network fail on profile save | User nghĩ đã lưu | Keep dirty state, retry |
| 14 | Google OAuth callback fail | Login loop | Error page with retry email login |
| 15 | Reset token expired | User stuck | Clear expired token message + request new link |
| 16 | Order status stale | User không biết paid/pending thật | Status refresh / updated timestamp |
| 17 | Payment transaction missing on order detail | Status ambiguous | Show payment unavailable + support reference |
| 18 | Order item product deleted | Detail page breaks | Use denormalized product_name fallback |
| 19 | Product image deleted | Broken image | Fallback thumbnail |
| 20 | Long order address | Layout breaks mobile | Wrap and collapse sections |
| 21 | Order paid but cart still has items | Confusing | Clear cart on paid, show order confirmation |
| 22 | Cancelled order still reviewable | Invalid review | Hide review CTA unless eligible |
| 23 | Pending order reviewable | Fake reviews | Require paid/fulfilled status |
| 24 | Review duplicate same order item | Spam/duplicates | One review per order item |
| 25 | Review order item mismatch | Review wrong product | Verify order_number owns orderItem |
| 26 | Review rating missing | Validation fail | Highlight star rating |
| 27 | Review content too long/empty | Poor moderation | Character counter, inline limits |
| 28 | Review image wrong type | Upload fail | Pre-validate allowed image types |
| 29 | Review image too large | Slow/fail upload | Size limit, compress guidance |
| 30 | Review upload partial fail | Some images lost | Show per-image status |
| 31 | Review submit double click | Duplicate review | Disable submit + idempotency |
| 32 | Review submit offline | User loses text | Keep draft, retry |
| 33 | Admin deletes review while user views order | Stale CTA/status | Refresh after action |
| 34 | Order email not delivered | User lacks receipt | Resend receipt CTA/support |
| 35 | Marketing opt-in unclear | Compliance risk | Explicit checkbox, no prechecked if required |
| 36 | Account page mobile tabs overflow | Navigation poor | Responsive tabs/dropdown |
| 37 | Screen reader cannot read status badges | Accessibility fail | Text labels + ARIA |
| 38 | Logout during checkout/account | User loses journey | Confirm if unsaved changes |
| 39 | Reset password page token email mismatch | Security confusion | Generic secure error, request new link |
| 40 | Rate limit login/reset | User sees generic fail | Show wait time and retry later |