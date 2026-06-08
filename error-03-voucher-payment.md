# Error 03 - Voucher and Payment

## Scope

Luồng: Apply/remove voucher -> select payment method -> gateway initiate -> processing -> confirm/cancel.

## Errors

| # | Error | Impact | Suggested UX / QA expectation |
|---|---|---|---|
| 1 | Voucher code không tồn tại | User không biết sai code hay hết hạn | Message Invalid voucher rõ |
| 2 | Voucher đã dùng | User retry vô ích | Message already used |
| 3 | Voucher email mismatch | User không hiểu vì sao code fail | Show code tied to another email copy |
| 4 | Voucher session còn nhưng voucher đã used | Discount stale | Clear session voucher, recalc totals |
| 5 | Apply voucher khi cart empty | JSON 422 khó hiểu | Disable voucher input hoặc empty cart message |
| 6 | Remove voucher network fail | Total UI không khớp server | Retry + refetch totals |
| 7 | Double click Apply voucher | Race condition discount | Disable apply button while pending |
| 8 | Apply voucher rồi đổi email | Voucher vẫn hiển thị nhưng không hợp lệ | Revalidate voucher on email change |
| 9 | Apply voucher rồi cart total đổi | Discount sai | Recalculate discount on cart changes |
| 10 | Voucher percent vượt subtotal | Negative total | Clamp total >= 0 |
| 11 | Voucher rollback fail khi gateway lỗi | Voucher bị khóa | Release voucher in rollback, test DB state |
| 12 | Hai checkout dùng cùng voucher | Duplicate discount | DB-level used lock/transaction |
| 13 | Payment method disabled after page load | Submit fails | Refresh method list, show choose another |
| 14 | Payment settings misconfigured | Gateway unavailable | Admin warning + customer fallback |
| 15 | Gateway initiate throws exception | Pending order giữ stock | Rollback checkout, restore stock |
| 16 | Gateway returns configured=false | User stuck | Treat as unavailable, show support path |
| 17 | Gateway redirect URL empty | Broken redirect | Validate before redirect |
| 18 | Redirect gateway blocked/pop-up issue | User cannot pay | Use full-page redirect, provide fallback link |
| 19 | Customer closes gateway tab | Pending order unresolved | Resume pending payment banner |
| 20 | Customer returns without confirm | Order stays pending | Clear next steps on processing page |
| 21 | Payment confirm false | User unsure if paid | Transaction failed + retry CTA |
| 22 | Payment confirm double submit | Duplicate capture | Disable confirm, gateway idempotency |
| 23 | PayPal approve succeeds but confirm request fails | Paid externally, app pending | Sync by PayPal order id |
| 24 | PayPal capture succeeds but mark paid fails | Payment/order mismatch | Transactional mark paid + reconciliation job |
| 25 | Proof transfer upload missing | Admin cannot verify | Require proof if method needs it |
| 26 | Proof file too large/wrong type | Upload fails late | Validate before upload, show allowed types |
| 27 | Proof upload network interruption | User loses payment proof | Retry upload, keep pending order |
| 28 | Processing page re-initiates gateway repeatedly | Duplicate gateway sessions | Reuse existing gateway transaction id if possible |
| 29 | Cancel pending after payment completed externally | Stock restored wrongly | Check gateway status before cancel |
| 30 | Cancel pending double click | Double stock restore | Idempotent cancel, status guard |
| 31 | Currency mismatch payment amount | Payment gateway amount differs | Lock currency/amount in order |
| 32 | Tax/shipping changes after initiate | Gateway amount stale | Freeze totals at order creation |
| 33 | Payment transaction missing | Confirm redirects weirdly | Error state + support reference |
| 34 | Admin changes order paid while customer processing | Customer sees stale pending | Refresh order status |
| 35 | Expired session on confirm | User paid but cart/session gone | Confirm by order_number, not session only |
| 36 | Gateway webhook arrives after rollback | Cancelled order becomes paid incorrectly | Verify order status transition rules |
| 37 | Mail paid fails after mark paid | User lacks receipt | Queue retry, show confirmation page |
| 38 | Customer refreshes paid confirmation | Duplicate mail if not guarded | Send once by paid_at/mail status |
| 39 | Payment method label/logo missing | Trust issue | Fallback label/icon |
| 40 | Payment instructions unclear | User abandons payment | Step-by-step processing copy |