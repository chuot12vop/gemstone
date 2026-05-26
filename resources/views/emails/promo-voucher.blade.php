@component('mail::message')
# Your {{ $voucher->percent }}% off code

Thanks for signing up at **{{ $siteName }}**.

Use this voucher at checkout:

@component('mail::panel')
**{{ $voucher->code }}**
@endcomponent

Enter the code on the checkout page (same email as this signup). The discount applies once to your order subtotal.

@component('mail::button', ['url' => route('shop.products.index')])
Shop now
@endcomponent

Thanks,<br>
{{ $siteName }}
@endcomponent
