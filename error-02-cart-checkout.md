# Error 02 - Cart to Checkout

## Scope

Luồng: Cart -> Checkout page -> Place order -> Processing/confirmation handoff.

## Errors

| # | Error | Impact | Suggested UX / QA expectation |
|---|---|---|---|
| 1 | Cart empty nhưng vẫn vào checkout | User gặp form vô nghĩa | Redirect cart, empty state rõ |
| 2 | Cart chứa product inactive | Checkout fail muộn | Remove item, show Cart updated |
| 3 | Cart chứa variant inactive | Item biến mất không rõ | Inline warning item unavailable |
| 4 | Qty cart vượt stock hiện tại | Oversell hoặc fail lúc place | Clamp qty, show Only X left |
| 5 | Stock bằng 0 sau khi cart mở | User điền form xong mới lỗi | Check trước place, preserve form input |
| 6 | Không có payment method enabled | Checkout không hoàn tất | Redirect cart, message No payment methods available |
| 7 | Payment method bị tắt khi user đang checkout | Place order lỗi | Refresh methods, ask user choose another method |
| 8 | Double click Place order | Tạo nhiều pending order | Disable submit, idempotency key |
| 9 | Browser back rồi submit lại | Duplicate order/pending conflict | Detect existing pending order, explain |
| 10 | Existing pending order bị cancel khi checkout mới | User mất order cũ | Confirmation banner trước khi replace pending |
| 11 | Gateway timeout sau order created | Stock/voucher bị giữ | Rollback order, restore stock, release voucher |
| 12 | Gateway returns error payload | User không biết có order chưa | Show retry + order status clarity |
| 13 | Order created nhưng mail fail | Place order bị xem như fail nếu không isolate | Queue mail, do not block checkout |
| 14 | Email invalid | Validation fail | Inline email format error |
| 15 | Phone invalid / quá ít số | Validation fail khó hiểu | Country-aware phone hint |
| 16 | Shipping country unsupported | User điền xong bị chặn | Country selector only supported countries |
| 17 | Postcode format sai | Delivery/payment fail | Inline postcode hints by country |
| 18 | Required address missing | Error summary bị bỏ sót | Scroll to first error |
| 19 | Long address/company text | Layout/payment note vỡ | Max length + wrapping |
| 20 | Tax/shipping calculation stale | Total sai | Recalculate on cart/address/voucher changes |
| 21 | Currency changed during checkout | Display total mismatch | Lock currency per checkout attempt or recalc |
| 22 | Cart session expires during form filling | Place redirects cart, user mất dữ liệu | Keep form draft, show session expired |
| 23 | CSRF expires | 419 page | Friendly retry flow, preserve inputs |
| 24 | Network offline on place order | User spam submit | Offline banner, retry safely |
| 25 | Slow place order | User thinks app frozen | Button loading, progress state |
| 26 | Processing redirect fails | User stuck after payment initiate | Provide manual order link |
| 27 | Checkout page refresh after order pending | User uncertain status | Resume pending order banner |
| 28 | Cart total differs from order total | Trust issue | Price changed notice before submit |
| 29 | Product image missing in order summary | Poor confidence | Thumbnail fallback |
| 30 | Mobile keyboard hides Place order | Conversion loss | Sticky CTA safe-area aware |
| 31 | Error message only top of long form | User cannot find bad field | Error summary + field-level errors |
| 32 | Payment validation extra fields missing | Gateway fails after submit | Render gateway-specific fields clearly |
| 33 | Guest login during checkout loses cart | Abandoned checkout | Merge cart and return to checkout |
| 34 | Auth session expires mid-checkout | Account-only data unavailable | Continue as guest or reauth modal |
| 35 | Order item denormalization fails | Admin/order detail missing variant data | Assert variant label/options persisted |