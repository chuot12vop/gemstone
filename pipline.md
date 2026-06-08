# Pipeline hệ thống

Tài liệu này mô tả luồng hoạt động của toàn bộ hệ thống Laravel, từ request vào route, qua controller, service, model, database, đến view/email/session.

## 1. Tổng quan kiến trúc

Hệ thống chia thành 2 vùng chính:

- Shop/storefront: khách hàng xem sản phẩm, đăng ký/đăng nhập, thêm giỏ hàng, checkout, thanh toán, gửi liên hệ, đánh giá đơn hàng.
- Admin: quản trị danh mục, sản phẩm, bài viết, trang nội dung, đơn hàng, thanh toán, đánh giá, liên hệ, cấu hình giao diện/cài đặt.

Luồng xử lý chuẩn:

```text
Browser
  -> routes/web.php
  -> Middleware
  -> Controller
  -> Service/Support helper
  -> Model/Eloquent
  -> Database
  -> View/Redirect/JSON/Email
```

Các thành phần chính:

- Routes: `routes/web.php`
- Shop controllers: `app/Http/Controllers/Shop`
- Admin controllers: `app/Http/Controllers/Admin`
- Models: `app/Models`
- Services: `app/Services`
- Support helpers: `app/Support`
- Mail classes: `app/Mail`
- Views/assets: `resources`, `public`
- Database schema: `database/migrations`

## 2. Module route và middleware

### Shop routes

Shop routes dùng guard mặc định `web`:

- Public pages: `/`, `/catalog`, `/product/{product}`, `/news`, `/about`, `/contact`, policy pages.
- Guest auth: `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`.
- Auth account: `/account`, `/account/profile`, `/account/orders`, `/account/orders/{order_number}`.
- Cart: `/cart`, `/cart/add`, `/cart/update`, `/cart/remove`.
- Checkout: `/checkout`, `/checkout/place`, `/checkout/processing/{order_number}`, `/checkout/confirm/{order_number}`.
- Review: `/order/{order_number}/review/{orderItem}`.
- Currency: `/currency`.
- Promo/welcome: `/promo-signup`, `/welcome-offer`.

Middleware chính:

- `guest`: chỉ khách chưa đăng nhập dùng auth pages.
- `auth`: tài khoản khách hàng, account pages, logout.
- `throttle:20,1`: giới hạn tốc độ tạo order/thanh toán.
- CSRF/session/cookie middleware từ Laravel web stack.

### Admin routes

Admin routes có prefix `/admin`, name prefix `admin.`.

Luồng:

```text
/admin/login
  -> AuthController
  -> guard admin
  -> session admin

/admin/*
  -> middleware auth:admin
  -> Admin controller
  -> Model/Service
  -> View/Redirect
```

Admin modules:

- Dashboard
- Products
- Categories
- Posts
- Pages
- Brands
- Certificates
- Currency
- Orders
- Payments
- Contacts
- Reviews
- Settings
- Interface
- About

## 3. Module authentication khách hàng

File chính:

- `Shop/Auth/LoginController.php`
- `Shop/Auth/RegisterController.php`
- `Shop/Auth/ForgotPasswordController.php`
- `Shop/Auth/ResetPasswordController.php`
- `Shop/Auth/GoogleAuthController.php`
- `Models/User.php`

Luồng đăng ký:

```text
GET /register
  -> RegisterController@show
  -> view form

POST /register
  -> validate dữ liệu
  -> tạo User
  -> login user
  -> redirect shop/account
```

Luồng đăng nhập email/password:

```text
GET /login
  -> LoginController@show

POST /login
  -> validate credentials
  -> Auth::attempt()
  -> tạo session
  -> redirect intended/home/account
```

Luồng Google OAuth:

```text
GET /auth/google
  -> GoogleAuthController@redirect
  -> redirect Google

GET /auth/google/callback
  -> GoogleAuthController@callback
  -> lấy thông tin Google
  -> tìm/tạo User
  -> login user
  -> redirect
```

Luồng quên/reset mật khẩu:

```text
GET /forgot-password
  -> hiển thị form email

POST /forgot-password
  -> gửi reset link

GET /reset-password/{token}
  -> hiển thị form đổi mật khẩu

POST /reset-password
  -> validate token/email/password
  -> cập nhật password
```

## 4. Module account khách hàng

File chính:

- `Shop/AccountController.php`
- `Models/User.php`
- `Models/Order.php`
- `Models/OrderItem.php`

Luồng account:

```text
User đăng nhập
  -> GET /account
  -> AccountController@index
  -> lấy thông tin user + đơn gần đây
  -> view account dashboard
```

Luồng profile:

```text
GET /account/profile
  -> form profile

POST /account/profile
  -> validate
  -> cập nhật User

POST /account/profile/password
  -> validate password hiện tại
  -> cập nhật password mới
```

Luồng đơn hàng cá nhân:

```text
GET /account/orders
  -> lấy orders theo user_id

GET /account/orders/{order_number}
  -> tìm order thuộc user
  -> load order items/payment transactions
  -> view chi tiết
```

## 5. Module catalog/product

File chính:

- `Shop/HomeController.php`
- `Shop/CatalogController.php`
- `Shop/ProductController.php`
- `Models/Product.php`
- `Models/ProductVariant.php`
- `Models/ProductImage.php`
- `Models/ProductVariantHoverImage.php`
- `Models/ProductAttribute.php`
- `Models/Category.php`
- `Models/Brand.php`
- `Models/Certificate.php`
- `Support/ProductPricing.php`
- `Support/ProductVariantOptions.php`
- `Support/ProductDetailPolicies.php`

Luồng trang chủ:

```text
GET /
  -> HomeController@index
  -> đọc settings/home sections/products/categories/posts
  -> render shop.home
```

Luồng catalog:

```text
GET /catalog
  -> CatalogController@index
  -> load categories/products
  -> filter/sort/paginate
  -> render catalog

GET /catalog/{category}
  -> tìm category
  -> load products thuộc category
  -> render category page

GET /product
  -> CatalogController@products
  -> danh sách sản phẩm
```

Luồng chi tiết sản phẩm:

```text
GET /product/{product}
  -> ProductController@show
  -> load product, category, brand, images, variants, attributes, upsells, reviews
  -> tính giá/discount/options
  -> render product detail
```

Dữ liệu sản phẩm chính:

- `products`: thông tin sản phẩm, giá, thumbnail/image, trạng thái, category/brand.
- `product_variants`: biến thể, giá, tồn kho, màu swatch, default/active.
- `product_images`: thư viện ảnh.
- `product_variant_hover_images`: ảnh hover theo biến thể.
- `product_attributes`: thuộc tính sản phẩm.
- `product_upsells`: sản phẩm bán kèm/giảm giá.
- `reviews`, `review_images`: đánh giá và ảnh review.

## 6. Module cart

File chính:

- `Shop/CartController.php`
- `Services/CartService.php`
- `Models/Product.php`
- `Models/ProductVariant.php`
- `Support/ProductPricing.php`

Cart lưu trong session key `cart`.

Cấu trúc item trong session:

```text
cart[variant_id] = {
  qty,
  unit_price_usd,
  product_id,
  upsell_parent_product_id
}
```

Luồng xem giỏ:

```text
GET /cart
  -> CartController@index
  -> CartService@buildLines
  -> validate product/variant active
  -> giới hạn qty theo stock
  -> tính line price
  -> render cart
```

Luồng thêm sản phẩm:

```text
POST /cart/add
  -> CartController@add
  -> validate product/variant/quantity
  -> CartService@add
  -> lưu session cart
  -> redirect/json response
```

Luồng thêm bundle/upsell:

```text
POST /cart/add-bundle
  -> CartController@addBundle
  -> thêm nhiều variant
  -> lưu parent product để tính giá upsell
```

Luồng cập nhật/xóa:

```text
POST /cart/update
  -> CartService@set
  -> nếu qty < 1 thì xóa item

POST /cart/remove
  -> CartService@remove
```

CartService xử lý:

- Đọc/chuẩn hóa session cart.
- Kiểm tra variant active và product active.
- Giới hạn số lượng theo stock.
- Tính giá gốc hoặc giá đã lưu.
- Tính giá upsell nếu parent product còn trong cart.
- Build lines để checkout/cart dùng chung.

## 7. Module checkout

File chính:

- `Shop/CheckoutController.php`
- `Services/CartService.php`
- `Services/CurrencyService.php`
- `Services\VoucherService.php`
- `Services\OrderMailService.php`
- `Services\Payment\PaymentMethodRegistry.php`
- `Support\CheckoutCountries.php`
- `Support\CheckoutShipping.php`
- `Support\PhoneValidation.php`
- `Support\PromoCheckoutSession.php`
- `Support\ShippingAddressFormatter.php`
- `Models\Order.php`
- `Models\OrderItem.php`
- `Models\PaymentTransaction.php`
- `Models\Voucher.php`

### 7.1 Luồng mở checkout

```text
GET /checkout
  -> CheckoutController@index
  -> CartService@buildLines
  -> nếu cart rỗng: redirect /cart
  -> PaymentMethodRegistry@enabled
  -> nếu không có method: redirect /cart
  -> tính subtotal
  -> lấy voucher session nếu có
  -> CheckoutShipping::orderAmounts
  -> CurrencyService convert/format
  -> render shop.checkout.index
```

Checkout page hiển thị:

- Thông tin contact/customer email.
- Địa chỉ giao hàng.
- Danh sách sản phẩm.
- Voucher.
- Shipping/tax/total.
- Payment methods enabled.
- Express checkout config nếu PayPal sẵn sàng.

### 7.2 Luồng apply/remove voucher

```text
POST /checkout/voucher
  -> validate voucher_code + customer_email
  -> VoucherService@findApplicable
  -> lưu checkout.voucher_id vào session
  -> tính discount/shipping/tax/total
  -> JSON response

DELETE /checkout/voucher
  -> xóa checkout.voucher_id
  -> tính lại total
  -> JSON response
```

Voucher chỉ hợp lệ khi:

- Code tồn tại.
- Chưa dùng.
- Email khớp điều kiện voucher nếu có.
- Có thể tính discount theo subtotal.

### 7.3 Luồng đặt hàng thường

```text
POST /checkout/place
  -> lấy payment_method
  -> PaymentMethodRegistry@findEnabled
  -> validate form + gateway validationRules
  -> CartService@buildLines
  -> resolve voucher
  -> tính subtotal/discount/shipping/tax/total
  -> cancel pending order cũ trong session
  -> DB transaction:
       tạo Order status=pending
       mark voucher used nếu có
       tạo OrderItem cho từng cart line
       trừ stock ProductVariant
       sync ProductVariantOptions denormalized
       tạo PaymentTransaction status=pending
  -> subscribe marketing email nếu opt-in
  -> gateway->initiate(order, request)
  -> nếu gateway lỗi:
       rollback checkout
       restore stock
       cancel order
       release voucher
  -> update latest PaymentTransaction gateway_transaction_id/notes
  -> lưu pending order vào session
  -> gửi mail OrderPlacedMail
  -> redirect theo PaymentInitiationResult
```

Kết quả initiate có thể là:

- `TYPE_REDIRECT`: redirect sang gateway.
- `TYPE_COMPLETED`: thanh toán xong ngay, mark paid.
- `TYPE_VIEW`: tới `/checkout/processing/{order_number}`.

### 7.4 Luồng express PayPal

```text
POST /checkout/express/paypal
  -> kiểm tra PayPal enabled + configured
  -> validate dữ liệu nhẹ hơn checkout thường
  -> áp dụng default cho email/address nếu thiếu
  -> CartService@buildLines
  -> resolve voucher
  -> DB transaction tạo Order/OrderItem/PaymentTransaction
  -> PayPalGateway@initiate
  -> nếu thành công: trả paypal_order_id + confirm_url JSON
  -> client PayPal SDK approve
  -> POST /checkout/confirm/{order_number}
```

### 7.5 Luồng processing payment

```text
GET /checkout/processing/{order_number}
  -> tìm Order
  -> lấy PaymentTransaction đầu tiên
  -> PaymentMethodRegistry@find(method)
  -> nếu order paid: redirect order show
  -> gateway->initiate lại để lấy viewData mới
  -> render shop.checkout.processing
```

Trang processing dùng cho method cần UI xác nhận/chuyển khoản/proof.

### 7.6 Luồng confirm payment

```text
POST /checkout/confirm/{order_number}
  -> tìm Order
  -> lấy PaymentTransaction
  -> resolve gateway
  -> gateway->confirm(order, request)
  -> nếu false:
       update tx failed
       redirect processing/json 422
  -> nếu gateway không tự mark paid:
       redirect order show với trạng thái chờ verify
  -> nếu gateway marksOrderPaidOnConfirm:
       markOrderPaid
```

`markOrderPaid`:

```text
CartService@clear
  -> xóa voucher/pending session
  -> DB transaction:
       Order status = paid
       PaymentTransaction status = paid
       paid_at = now
       gateway_transaction_id = tx id
  -> OrderMailService@sendPaid
  -> redirect /order/{order_number}
```

### 7.7 Luồng cancel pending

```text
POST /checkout/cancel/{order_number}
  -> chỉ cho order status=pending
  -> restore stock
  -> Order status=cancelled
  -> PaymentTransaction status=cancelled
  -> xóa pending session
  -> redirect checkout
```

## 8. Module payment

File chính:

- `Services/Payment/Contracts/PaymentGateway.php`
- `Services/Payment/PaymentMethodRegistry.php`
- `Services/Payment/Gateways/AbstractPaymentGateway.php`
- `Services/Payment/Gateways/AbstractProofTransferGateway.php`
- `Services/Payment/Gateways/PayPalGateway.php`
- `Services/Payment/Gateways/ApplePayGateway.php`
- `Services/Payment/Gateways/CashAppGateway.php`
- `Services/Payment/Gateways/VenmoGateway.php`
- `Services/Payment/Gateways/ZelleGateway.php`
- `Services/Payment/PayPal/PayPalApiClient.php`
- `Services/Payment/Stripe/StripeApiClient.php`
- `Services/Payment/Data/PaymentInitiationResult.php`
- `Providers/PaymentServiceProvider.php`
- `Models/PaymentTransaction.php`

Luồng registry:

```text
PaymentServiceProvider
  -> đăng ký gateway services
  -> tag gateway
  -> PaymentMethodRegistry nhận iterable<PaymentGateway>
  -> CheckoutController/Admin Payment dùng registry
```

Mỗi gateway cung cấp:

- `code()`: mã method, ví dụ `paypal`, `zelle`.
- `label()`: tên hiển thị.
- `isEnabled()`: bật/tắt theo settings admin.
- `validationRules()`: rule thêm cho checkout form.
- `initiate(Order, Request)`: bắt đầu thanh toán.
- `confirm(Order, Request)`: xác nhận thanh toán.
- `marksOrderPaidOnConfirm()`: gateway có tự chuyển order sang paid không.

Luồng method proof transfer:

```text
Customer chọn method proof transfer
  -> tạo Order pending
  -> tạo PaymentTransaction pending
  -> processing page hiển thị hướng dẫn
  -> customer upload/submit proof
  -> gateway confirm
  -> Order giữ pending/chờ admin verify
  -> admin cập nhật order/payment status
```

Luồng PayPal:

```text
Customer chọn PayPal hoặc Express PayPal
  -> PayPalGateway initiate
  -> PayPalApiClient tạo PayPal order
  -> nhận paypal_order_id/redirect/viewData
  -> customer approve
  -> confirm
  -> capture/sync payer details
  -> mark order paid
```

## 9. Module order

File chính:

- `Models\Order.php`
- `Models\OrderItem.php`
- `Models\PaymentTransaction.php`
- `Shop\CheckoutController.php`
- `Shop\AccountController.php`
- `Admin\OrderAdminController.php`
- `Services\OrderMailService.php`
- `Mail\OrderPlacedMail.php`
- `Mail\OrderPaidMail.php`

Trạng thái order chính:

- `pending`: đã tạo, chờ thanh toán/xác minh.
- `paid`: đã thanh toán.
- `cancelled`: bị hủy/rollback.

Luồng tạo order nằm trong checkout transaction:

```text
Cart lines
  -> Order
  -> OrderItem[]
  -> PaymentTransaction
  -> stock decrement
  -> voucher mark used
```

Luồng xem order:

```text
GET /order/{order_number}
  -> CheckoutController@confirmation
  -> load items.review + paymentTransactions
  -> render confirmation

GET /account/orders/{order_number}
  -> AccountController@orderShow
  -> kiểm tra user sở hữu order
  -> render account order detail
```

Luồng admin quản lý order:

```text
GET /admin/orders
  -> OrderAdminController@index
  -> filter/list orders

GET /admin/orders/{order}
  -> OrderAdminController@show
  -> load items/payment/customer

POST /admin/orders/{order}/status
  -> cập nhật status
  -> có thể ảnh hưởng fulfillment/payment workflow
```

Email:

```text
Order created successfully
  -> OrderMailService@sendPlaced
  -> OrderPlacedMail

Order marked paid
  -> OrderMailService@sendPaid
  -> OrderPaidMail
```

## 10. Module review

File chính:

- `Shop\ReviewController.php`
- `Admin\ReviewAdminController.php`
- `Models\Review.php`
- `Models\ReviewImage.php`
- `Models\Order.php`
- `Models\OrderItem.php`

Luồng khách tạo review:

```text
GET /order/{order_number}/review/{orderItem}
  -> kiểm tra order/order item
  -> hiển thị form review

POST /order/{order_number}/review/{orderItem}
  -> validate rating/content/images
  -> tạo Review
  -> upload ReviewImage nếu có
  -> redirect order show
```

Luồng admin quản lý review:

```text
GET /admin/reviews
  -> danh sách review

GET /admin/reviews/create
POST /admin/reviews
  -> tạo review thủ công

GET /admin/reviews/{review}/edit
PUT /admin/reviews/{review}
  -> cập nhật review

DELETE /admin/reviews/{review}
  -> xóa review
```

Review liên kết với:

- User/customer info nếu có.
- Product.
- OrderItem.
- Review images.

## 11. Module contact

File chính:

- `Shop\ContactController.php`
- `Shop\PageController.php`
- `Admin\ContactAdminController.php`
- `Models\Contact.php`
- `Support\ContactFormSettings.php`

Luồng khách gửi contact:

```text
GET /contact
  -> PageController@contact
  -> render contact page

POST /contact
  -> ContactController@store
  -> validate form
  -> tạo Contact
  -> redirect success
```

Luồng admin xử lý contact:

```text
GET /admin/contacts
  -> list contacts

GET /admin/contacts/{contact}
  -> xem chi tiết

POST /admin/contacts/{contact}/status
  -> cập nhật trạng thái xử lý

DELETE /admin/contacts/{contact}
  -> xóa contact
```

## 12. Module content/page/news

File chính:

- `Shop\PostController.php`
- `Shop\PageController.php`
- `Admin\PostAdminController.php`
- `Admin\PageAdminController.php`
- `Models\Post.php`
- `Models\Page.php`

Luồng news:

```text
GET /news
  -> PostController@index
  -> load published posts
  -> render news list

GET /news/{post}
  -> PostController@show
  -> load post by slug/id
  -> render detail
```

Luồng static/policy pages:

```text
GET /about
GET /contact
GET /security-policy
GET /privacy-policy
GET /return-policy
GET /terms-of-service
GET /retail-policy
  -> PageController method tương ứng
  -> load Page/settings/support config
  -> render view
```

Luồng admin content:

```text
/admin/posts CRUD
  -> PostAdminController
  -> Post model

/admin/pages CRUD
  -> PageAdminController
  -> Page model
```

## 13. Module currency

File chính:

- `Shop\CurrencyController.php`
- `Admin\CurrencyAdminController.php`
- `Services\CurrencyService.php`
- `Models\CurrencyRate.php`

Luồng khách đổi tiền tệ:

```text
POST /currency
  -> CurrencyController@set
  -> validate currency code
  -> lưu currency vào session/cookie
  -> redirect back
```

Luồng hiển thị giá:

```text
Controller/View
  -> CurrencyService@currentCode
  -> CurrencyService@convertUsdToCurrent
  -> CurrencyService@formatUsd
  -> hiển thị tiền theo currency hiện tại
```

Luồng admin cập nhật tỷ giá:

```text
GET /admin/currency
  -> CurrencyAdminController@index
  -> view rates

POST /admin/currency/save
  -> validate rates
  -> lưu CurrencyRate
```

## 14. Module voucher và marketing

File chính:

- `Services\VoucherService.php`
- `Models\Voucher.php`
- `Support\MarketingSubscribers.php`
- `Support\PromoCheckoutSession.php`
- `Mail\PromoVoucherMail.php`
- `Shop\PromoSignupController.php`
- `Shop\WelcomeOfferController.php`

Luồng promo signup:

```text
POST /promo-signup
  -> PromoSignupController@store
  -> validate email
  -> tạo hoặc tìm Voucher
  -> lưu subscriber email/session nếu cần
  -> gửi PromoVoucherMail
  -> response redirect/json
```

Luồng welcome offer:

```text
POST /welcome-offer
  -> WelcomeOfferController@store
  -> validate email
  -> tạo/assign voucher
  -> gửi voucher mail
```

Luồng voucher checkout:

```text
Customer nhập code + email
  -> VoucherService@findApplicable
  -> session checkout.voucher_id
  -> checkout place
  -> VoucherService@markUsed(voucher, order)
  -> nếu rollback: VoucherService@release
```

## 15. Module admin catalog

File chính:

- `Admin\ProductAdminController.php`
- `Admin\CategoryAdminController.php`
- `Admin\BrandAdminController.php`
- `Admin\CertificateAdminController.php`
- `Services\PublicImageStore.php`
- `Services\ImageWebpEncoder.php`
- `Models\Product.php`
- `Models\ProductVariant.php`
- `Models\ProductImage.php`
- `Models\ProductAttribute.php`
- `Models\Category.php`
- `Models\Brand.php`
- `Models\Certificate.php`

Luồng quản trị sản phẩm:

```text
GET /admin/products
  -> ProductAdminController@index
  -> list/filter/search products

GET /admin/products/create
  -> ProductAdminController@create
  -> load categories/brands/certificates
  -> render form

POST /admin/products
  -> validate dữ liệu
  -> upload/convert image nếu có
  -> tạo Product
  -> tạo variants/images/attributes/upsells
  -> sync denormalized variant options
  -> redirect edit/index

GET /admin/products/{product}/edit
  -> load product relations
  -> render edit form

PUT /admin/products/{product}
  -> validate
  -> update product
  -> update variants/images/attributes/upsells
  -> sync denormalized fields

DELETE /admin/products/{product}
  -> xóa product hoặc chuyển trạng thái theo logic controller
```

Luồng category/brand/certificate:

```text
/admin/categories CRUD
/admin/brands CRUD
/admin/certificates CRUD
  -> validate
  -> upload image/logo nếu có
  -> save model
```

## 16. Module admin settings/interface/about/payment

File chính:

- `Admin\SettingAdminController.php`
- `Admin\InterfaceAdminController.php`
- `Admin\AboutAdminController.php`
- `Admin\PaymentAdminController.php`
- `Models\Setting.php`
- `Support\ShopFrontSettings.php`
- `Support\HomeSectionSettings.php`
- `Support\AboutPageSettings.php`
- `Support\PaymentLogoSettings.php`
- `Support\PaymentMethodLogos.php`
- `Support\PublicAssetUrl.php`

Luồng settings chung:

```text
GET /admin/settings
  -> SettingAdminController@index
  -> load Setting records
  -> render form

POST /admin/settings/save
  -> validate
  -> save Setting key/value
```

Luồng interface:

```text
GET /admin/interface
  -> InterfaceAdminController@index
  -> load logo/banner/home/interface settings

POST /admin/interface/save
  -> validate
  -> upload assets nếu có
  -> save settings
```

Luồng about:

```text
GET /admin/about
  -> AboutAdminController@index
  -> load about settings

POST /admin/about/save
  -> save about sections/assets/content
```

Luồng payment settings:

```text
GET /admin/payments
  -> PaymentAdminController@index
  -> PaymentMethodRegistry@all
  -> load payment settings/logos
  -> render form

POST /admin/payments/settings
  -> validate enabled/config/display fields
  -> save Setting
  -> ảnh hưởng PaymentGateway@isEnabled
```

## 17. Module upload/asset

File chính:

- `Services\PublicImageStore.php`
- `Services\ImageWebpEncoder.php`
- `Support\FileUploadAccept.php`
- `Support\PublicAssetUrl.php`

Luồng upload ảnh:

```text
Admin form upload file
  -> Controller validate file type/size
  -> PublicImageStore lưu file vào public/storage hoặc disk public
  -> ImageWebpEncoder convert nếu cần
  -> lưu path vào model/setting
  -> PublicAssetUrl tạo URL hiển thị
```

File upload dùng cho:

- Product images/thumbnails.
- Category images.
- Brand/certificate assets.
- Interface/home/about assets.
- Review images.
- Payment logos.

## 18. Module email

File chính:

- `Services\OrderMailService.php`
- `Mail\OrderPlacedMail.php`
- `Mail\OrderPaidMail.php`
- `Mail\PromoVoucherMail.php`

Luồng order email:

```text
Checkout place thành công
  -> OrderMailService@sendPlaced
  -> OrderPlacedMail
  -> customer email

Order paid
  -> OrderMailService@sendPaid
  -> OrderPaidMail
  -> customer email
```

Luồng promo email:

```text
Promo/welcome signup
  -> tạo voucher
  -> PromoVoucherMail
  -> subscriber email
```

## 19. Dữ liệu chính và quan hệ

Nhóm user/auth:

- `users`: khách hàng.
- `admins`: quản trị viên.
- `password_resets`: reset password.

Nhóm catalog:

- `categories`: danh mục.
- `brands`: thương hiệu.
- `certificates`: chứng nhận.
- `products`: sản phẩm.
- `product_variants`: biến thể sản phẩm.
- `product_images`: ảnh sản phẩm.
- `product_variant_hover_images`: ảnh hover theo biến thể.
- `product_attributes`: thuộc tính.
- `product_upsells`: sản phẩm upsell/bundle.

Nhóm order/payment:

- `orders`: đơn hàng.
- `order_items`: dòng sản phẩm trong đơn.
- `payment_transactions`: giao dịch thanh toán.
- `vouchers`: voucher/discount.

Nhóm content/support:

- `posts`: tin tức/blog.
- `pages`: trang nội dung.
- `reviews`: đánh giá.
- `review_images`: ảnh đánh giá.
- `contacts`: liên hệ.
- `settings`: cấu hình hệ thống.
- `currency_rates`: tỷ giá tiền tệ.

Quan hệ chính:

```text
User 1 - n Order
Order 1 - n OrderItem
Order 1 - n PaymentTransaction
OrderItem n - 1 Product
OrderItem n - 1 ProductVariant
Product n - 1 Category
Product n - 1 Brand
Product 1 - n ProductVariant
Product 1 - n ProductImage
Product 1 - n ProductAttribute
Product n - n Product (upsells)
Product 1 - n Review
Review 1 - n ReviewImage
Voucher 0/1 - 1 Order
```

## 20. Luồng end-to-end: khách mua hàng

```text
1. Customer vào /
2. Xem catalog/category/product
3. Chọn variant/quantity
4. POST /cart/add
5. CartService lưu session cart
6. GET /cart kiểm tra giỏ
7. GET /checkout
8. CheckoutController build lines, totals, methods
9. Customer nhập email/address/voucher/payment method
10. POST /checkout/place
11. Validate dữ liệu + gateway rules
12. Tạo Order pending
13. Tạo OrderItems
14. Trừ stock variants
15. Tạo PaymentTransaction pending
16. Gateway initiate
17. Gửi OrderPlacedMail
18. Redirect processing/gateway hoặc mark paid
19. POST /checkout/confirm/{order_number}
20. Gateway confirm
21. Nếu paid: clear cart, Order paid, PaymentTransaction paid, gửi OrderPaidMail
22. Customer xem /order/{order_number}
23. Customer có thể review order item
```

## 21. Luồng end-to-end: admin vận hành

```text
1. Admin đăng nhập /admin/login
2. Guard auth:admin tạo session
3. Admin quản lý catalog:
   - categories
   - brands
   - certificates
   - products
   - variants
   - images
4. Admin cấu hình:
   - currency rates
   - payment methods
   - settings
   - interface/home/about
5. Customer đặt hàng
6. Admin xem /admin/orders
7. Admin xem chi tiết order/payment
8. Admin cập nhật status nếu cần
9. Admin xử lý contact/review/content
```

## 22. Luồng lỗi và rollback quan trọng

Checkout gateway lỗi:

```text
gateway->initiate throw/error
  -> rollbackFailedCheckout
  -> restoreOrderStock
  -> Order status=cancelled
  -> PaymentTransaction status=cancelled
  -> VoucherService@release nếu voucher thuộc order
  -> xóa pending/last order session
  -> redirect checkout error
```

Customer tạo checkout mới khi còn pending order:

```text
session checkout.pending_order tồn tại
  -> cancelPendingOrderBySession
  -> restore stock order cũ
  -> order cũ cancelled
  -> transaction cancelled
  -> tạo order mới
```

Customer hủy pending order:

```text
POST /checkout/cancel/{order_number}
  -> restore stock
  -> order cancelled
  -> transaction cancelled
  -> back checkout
```

Voucher lỗi:

```text
Voucher invalid/used/email mismatch
  -> không tạo order
  -> trả error form hoặc JSON 422
```

Cart lỗi:

```text
Cart empty hoặc all products inactive/out of stock
  -> redirect /cart với error
```

## 23. Điểm mở rộng hệ thống

Thêm payment gateway mới:

```text
1. Tạo class implements PaymentGateway
2. Implement code/label/isEnabled/validationRules/initiate/confirm
3. Đăng ký/tag trong PaymentServiceProvider
4. Thêm settings/logo nếu cần
5. Checkout tự thấy gateway qua PaymentMethodRegistry
```

Thêm module admin CRUD mới:

```text
1. Tạo migration/model
2. Tạo Admin controller
3. Thêm routes trong admin auth group
4. Tạo views
5. Thêm menu admin nếu có
```

Thêm cấu hình storefront:

```text
1. Thêm key/value trong Setting
2. Thêm Support settings class hoặc mở rộng class hiện có
3. Thêm form trong admin settings/interface/about
4. Đọc config trong controller/view
```

## 24. Tóm tắt module theo trách nhiệm

- `routes/web.php`: định nghĩa URL, middleware, controller action.
- `Shop controllers`: xử lý request khách hàng.
- `Admin controllers`: xử lý request quản trị.
- `Services`: nghiệp vụ dùng lại nhiều nơi như cart, currency, voucher, mail, payment.
- `Support`: helper/config nhỏ cho formatting, settings, validation, pricing.
- `Models`: map database và quan hệ dữ liệu.
- `Migrations`: schema database.
- `Mail`: template email và dữ liệu gửi mail.
- `resources/views`: giao diện render HTML.
- `session`: cart, checkout method, pending order, voucher, currency.