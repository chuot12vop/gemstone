# Error 01 - Product to Cart

## Scope

Luồng: Product detail / Catalog item -> Add to cart -> Mini cart / Cart session.

## Errors

| # | Error | Impact | Suggested UX / QA expectation |
|---|---|---|---|
| 1 | Product hết hàng nhưng nút Add to cart vẫn active | User click rồi mới lỗi | Disable CTA, label Out of stock, thêm Notify me |
| 2 | Variant hết hàng nhưng vẫn chọn được | Add cart fail, user không hiểu | Disable swatch/option, gạch sold out |
| 3 | Chưa chọn variant đã submit | Lỗi mơ hồ, user không biết sửa đâu | Highlight variant selector, message cụ thể |
| 4 | Double click Add to cart | Quantity tăng 2 lần | Disable button khi pending, backend chống duplicate |
| 5 | Spam tap quantity stepper | Qty vượt stock hoặc UI lag | Debounce, clamp min/max, show Only X left |
| 6 | Nhập qty 0, âm, chữ, thập phân | Validation lỗi hoặc cart sai | Input numeric, min 1, inline validation |
| 7 | Stock đổi sau khi page đã mở | UI báo còn hàng nhưng add fail | Server revalidate, message Only X left now |
| 8 | Hai user add cùng item stock thấp | Oversell nếu backend không lock | Validate stock tại checkout, lock/decrement an toàn |
| 9 | Product inactive/deleted sau khi mở page | 404/422 khó hiểu | Modal Product no longer available, CTA về catalog |
| 10 | Variant inactive/deleted sau khi render | Add fail hoặc cart item biến mất | Error rõ Product not available, refresh variant list |
| 11 | Giá đổi sau khi page mở | Cart price khác product page | Show price updated notice trước checkout |
| 12 | Unit price client gửi bị tamper | User mua sai giá nếu server tin client | Server tính giá authoritative |
| 13 | Upsell parent không còn trong cart | Discount sai | Recalculate, thông báo bundle discount removed |
| 14 | Bundle add thiếu item do hết stock | Cart thêm nửa bundle | Atomic add hoặc show partial failure rõ |
| 15 | Add cart mạng chậm | User click lại nhiều lần | Loading state + disabled CTA |
| 16 | Timeout nhưng server đã add | UI báo lỗi nhưng cart có item | Idempotency key, retry status, refresh cart badge |
| 17 | Offline khi add | Mất lựa chọn variant/qty | Keep state, show retry |
| 18 | CSRF/session expired | 419 khó hiểu | Message Session expired, reload token, keep selection |
| 19 | Cart session hết hạn | User tưởng item đã lưu | Recreate cart, notify Cart refreshed |
| 20 | Mini cart update fail sau add thành công | Badge/cart không khớp | Fallback refetch cart, show success based on server |
| 21 | Cart badge cached sai | User không tin hệ thống | Update badge from response, avoid stale cache |
| 22 | Variant image 404 | Broken image | Fallback thumbnail |
| 23 | Product/variant name quá dài | Mini cart layout vỡ | Clamp text, tooltip/expand |
| 24 | Sticky CTA mobile bị che | Không add được | Safe-area spacing, keyboard-aware layout |
| 25 | Toast success biến mất quá nhanh | User không biết đã add | Toast >= 4s, mini cart drawer |
| 26 | Redirect sau add làm mất scroll/filter | User khó tiếp tục mua | Stay on page, mini cart drawer |
| 27 | JS lỗi khiến form submit full page | Trải nghiệm lệch | Progressive enhancement, server redirect hợp lý |
| 28 | Guest cart merge sau login bị trùng | Qty cộng sai | Merge rule rõ, combine same variant once |
| 29 | Đổi currency rồi add cart | Display price lệch USD session price | Store base USD, format display only |
| 30 | Browser back về page cũ | Add stale variant/price | Revalidate on submit |
| 31 | Default variant không rõ | User add nhầm màu/size | Require explicit choice nếu có nhiều variant |
| 32 | Screen reader không biết sold out/selected | Accessibility fail | ARIA selected/disabled/live region |
| 33 | Product không có active variant | Dropdown rỗng | Empty state Currently unavailable |
| 34 | Cart contains stale inactive item | Item biến mất không giải thích | Remove stale item + show cart updated |
| 35 | Add bundle response JSON fail | UI không cập nhật | Handle non-JSON/500 fallback |