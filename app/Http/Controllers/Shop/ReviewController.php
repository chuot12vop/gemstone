<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Services\PublicImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Public-facing review submission flow.
 *
 * To leave a review, the customer must come from the order confirmation page
 * (we use the order_number + order_item_id pair as a "verified purchase"
 * proof — it's the same data they used to look up their order).
 *
 * Reviews land in `pending` and become visible on the product page once an
 * admin approves them via {@see \App\Http\Controllers\Admin\ReviewAdminController}.
 */
class ReviewController extends Controller
{
    public function __construct(private PublicImageStore $images) {}

    public function create(string $order_number, OrderItem $orderItem): View|RedirectResponse
    {
        $order = $this->findOrderOrFail($order_number);
        $this->ensureItemBelongsToOrder($orderItem, $order);

        if ($orderItem->review !== null) {
            return redirect()
                ->route('shop.order.show', ['order_number' => $order->order_number])
                ->with('error', 'You have already reviewed this product.');
        }

        $orderItem->load('product');
        if (! $orderItem->product) {
            return redirect()
                ->route('shop.order.show', ['order_number' => $order->order_number])
                ->with('error', 'This product is no longer available.');
        }

        return view('shop.reviews.create', [
            'title' => 'Write a review — '.$orderItem->product_name,
            'metaDescription' => 'Share your experience with this gemstone.',
            'order' => $order,
            'orderItem' => $orderItem,
            'product' => $orderItem->product,
        ]);
    }

    public function store(Request $request, string $order_number, OrderItem $orderItem): RedirectResponse
    {
        $order = $this->findOrderOrFail($order_number);
        $this->ensureItemBelongsToOrder($orderItem, $order);

        if ($orderItem->review !== null) {
            return redirect()
                ->route('shop.order.show', ['order_number' => $order->order_number])
                ->with('error', 'You have already reviewed this product.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'content' => 'required|string|max:5000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $imagePaths = $this->images->storeMany($request->file('images'), 'reviews');

        DB::transaction(function () use ($validated, $order, $orderItem, $imagePaths): void {
            $review = Review::query()->create([
                'product_id' => $orderItem->product_id,
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'user_id' => auth()->id(),
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'rating' => (int) $validated['rating'],
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'],
                'status' => Review::STATUS_PENDING,
            ]);

            foreach ($imagePaths as $idx => $path) {
                ReviewImage::query()->create([
                    'review_id' => $review->id,
                    'path' => $path,
                    'sort_order' => $idx,
                ]);
            }
        });

        return redirect()
            ->route('shop.order.show', ['order_number' => $order->order_number])
            ->with('success', 'Thanks for your review! It will appear once approved.');
    }

    private function findOrderOrFail(string $orderNumber): Order
    {
        return Order::query()->where('order_number', $orderNumber)->firstOrFail();
    }

    private function ensureItemBelongsToOrder(OrderItem $orderItem, Order $order): void
    {
        abort_if((int) $orderItem->order_id !== (int) $order->id, 404);
    }
}
