# Error 05 - Admin Product, Order, Payment

## Scope

Luồng: Admin login -> catalog CRUD -> order/payment operations -> settings/interface/payment config.

## Errors

| # | Error | Impact | Suggested UX / QA expectation |
|---|---|---|---|
| 1 | Admin session expired while editing product | Mất form dài | Reauth modal, autosave draft |
| 2 | Double click Save product | Duplicate variants/images | Disable save, idempotency |
| 3 | Product form validation late | Admin sửa nhiều lần | Inline validation per field |
| 4 | Product name/slug duplicate | Save fail | Slug availability check |
| 5 | Category/brand missing | Product cannot save | Required dropdown empty state + create link |
| 6 | Upload image wrong type | Save fail after upload | Pre-validate accept/type |
| 7 | Upload image too large | Slow/fail | Show max size before upload |
| 8 | Image WebP conversion fail | Product image missing | Keep original fallback, show warning |
| 9 | Storage permission/path fail | Broken storefront assets | Admin error with retry/support |
| 10 | Delete image accidentally | Data loss | Confirm + undo where possible |
| 11 | Variant stock negative | Bad inventory | Numeric min 0 validation |
| 12 | Variant price negative/invalid | Bad checkout totals | Currency numeric validation |
| 13 | No active variants on active product | Product page unavailable | Warning before publish |
| 14 | Default variant inactive | Wrong product default | Enforce one active default |
| 15 | Bulk variant edits partially save | Inconsistent catalog | Transaction save all or none |
| 16 | Upsell relation points inactive product | Broken bundle | Filter inactive, warning |
| 17 | Product delete with existing order items | Historical order broken | Soft delete/inactive, keep order denormalized |
| 18 | Admin list empty | Looks broken | Empty state + Create product CTA |
| 19 | Admin search no results | Confusing | Clear filters CTA |
| 20 | Long product data table on mobile | Hard to manage | Responsive columns, priority data |
| 21 | Order list filters combine to none | Admin thinks no orders | Filter chips + reset |
| 22 | Order status update double click | Duplicate status transitions | Disable action + audit log |
| 23 | Admin marks cancelled order paid | Invalid state | Status transition guard |
| 24 | Admin cancels paid order | Payment mismatch | Require refund workflow/confirmation |
| 25 | Manual status update does not update payment transaction | Order/payment inconsistent | Sync status or show split state |
| 26 | Payment proof image missing | Cannot verify | Broken proof fallback + ask customer |
| 27 | Payment transaction missing | Admin cannot reconcile | Create support warning, block paid action |
| 28 | Pending order stock held too long | Inventory locked | Expiry/auto-cancel pending orders |
| 29 | Restore stock twice | Inventory inflated | Idempotent cancel/rollback |
| 30 | Payment settings save invalid credentials | Gateway fails customers | Validate config/test connection |
| 31 | All payment methods disabled | Checkout unavailable | Admin warning/block save or storefront alert |
| 32 | Payment logo upload broken | Trust issue | Fallback text logo |
| 33 | Currency rate invalid/zero | Prices wrong | Min > 0 validation |
| 34 | Currency rate stale | Customer sees wrong amount | Last updated timestamp + admin warning |
| 35 | Settings save partial failure | Storefront mixed config | Transaction/config versioning |
| 36 | Interface banner image missing | Homepage broken | Preview + fallback image |
| 37 | Rich content XSS in pages/posts | Security risk | Sanitize/escape content |
| 38 | Admin deletes contact/review accidentally | Data loss | Confirm + soft delete/audit |
| 39 | Contact status double update | Wrong workflow | Disable pending action, show last updated |
| 40 | Review moderation publishes spam | Brand risk | Preview + status workflow |
| 41 | Admin login brute force | Security risk | Throttle + lockout UX |
| 42 | Admin role/permission missing | Unauthorized access | Role guard + disabled hidden actions |
| 43 | Admin CSRF expired on save | 419 loss | Preserve input, refresh token |
| 44 | Network fail during save | Admin uncertain | Retry, unsaved changes warning |
| 45 | Concurrent admins edit same product | Last write wins | Updated-at conflict warning |
| 46 | Settings cache not cleared | Admin sees saved but storefront old | Clear cache, show effective state |
| 47 | Mail settings broken | Order emails fail | Admin health check |
| 48 | Dashboard counts stale | Wrong operation decisions | Timestamp + refresh button |
| 49 | Pagination after delete empty page | Looks no data | Redirect previous page |
| 50 | Admin accessibility poor | Slow operations | Labels, focus states, keyboard actions |