@component('mail::message')
# Order #{{ $order->order_number }} received

Hi{{ $order->customer_name ? ' '.$order->customer_name : '' }},

Thank you for your order at **{{ $siteName }}**. We have received your order and it is being processed.

**Order total:** {{ $order->currency_code }} {{ number_format((float) $order->total_display, 2) }}

@component('mail::table')
| Item | Qty | Line total |
|:-----|:---:|-----------:|
@foreach($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ${{ number_format((float) $item->line_total_usd, 2) }} |
@endforeach
@endcomponent

@if($order->status === 'pending')
Your payment is being reviewed. We will email you once your order is confirmed.
@endif

@component('mail::button', ['url' => route('shop.order.show', ['order_number' => $order->order_number])])
View order
@endcomponent

Thanks,<br>
{{ $siteName }}
@endcomponent
