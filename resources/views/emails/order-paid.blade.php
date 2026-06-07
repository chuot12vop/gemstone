@component('mail::message')
# Order #{{ $order->order_number }} confirmed

Hi{{ $order->customer_name ? ' '.$order->customer_name : '' }},

Your payment for order **#{{ $order->order_number }}** has been confirmed. Thank you for shopping with **{{ $siteName }}**.

**Order total:** {{ $order->currency_code }} {{ number_format((float) $order->total_display, 2) }}

@component('mail::table')
| Item | Qty | Line total |
|:-----|:---:|-----------:|
@foreach($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ${{ number_format((float) $item->line_total_usd, 2) }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => route('shop.order.show', ['order_number' => $order->order_number])])
View order
@endcomponent

Thanks,<br>
{{ $siteName }}
@endcomponent
