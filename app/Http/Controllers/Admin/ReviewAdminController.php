<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Services\PublicImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewAdminController extends Controller
{
    public function __construct(private PublicImageStore $images) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $rating = (int) $request->get('rating', 0);
        $productId = (int) $request->get('product_id', 0);

        $query = Review::query()
            ->with(['product:id,name,slug', 'images'])
            ->latest();

        if ($q !== '') {
            $query->where(function ($w) use ($q): void {
                $w->where('content', 'like', '%'.$q.'%')
                    ->orWhere('title', 'like', '%'.$q.'%')
                    ->orWhere('customer_name', 'like', '%'.$q.'%')
                    ->orWhere('customer_email', 'like', '%'.$q.'%');
            });
        }
        if (in_array($status, Review::STATUSES, true)) {
            $query->where('status', $status);
        }
        if ($rating >= 1 && $rating <= 5) {
            $query->where('rating', $rating);
        }
        if ($productId > 0) {
            $query->where('product_id', $productId);
        }

        return view('admin.reviews.index', [
            'title' => 'Reviews',
            'breadcrumbs' => [['label' => 'Reviews']],
            'reviews' => $query->take(200)->get(),
            'q' => $q,
            'status' => $status,
            'rating' => $rating,
            'productId' => $productId,
            'statuses' => Review::STATUSES,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('admin.reviews.form', [
            'title' => 'New review',
            'breadcrumbs' => [
                ['label' => 'Reviews', 'url' => route('admin.reviews.index')],
                ['label' => 'New'],
            ],
            'review' => null,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => Review::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $imagePaths = $this->images->storeMany($request->file('images'), 'reviews');

        DB::transaction(function () use ($data, $imagePaths): void {
            $review = Review::query()->create($data);
            foreach ($imagePaths as $idx => $path) {
                ReviewImage::query()->create([
                    'review_id' => $review->id,
                    'path' => $path,
                    'sort_order' => $idx,
                ]);
            }
        });

        return redirect()->route('admin.reviews.index')->with('success', 'Review created.');
    }

    public function edit(Review $review): View
    {
        $review->load('images', 'product:id,name', 'order:id,order_number', 'orderItem:id,product_name');

        return view('admin.reviews.form', [
            'title' => 'Edit review',
            'breadcrumbs' => [
                ['label' => 'Reviews', 'url' => route('admin.reviews.index')],
                ['label' => '#'.$review->id],
            ],
            'review' => $review,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => Review::STATUSES,
        ]);
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $data = $this->validated($request, $review);
        $imagePaths = $this->images->storeMany($request->file('images'), 'reviews');
        $deleteIds = collect($request->input('delete_image_ids', []))->map(fn ($v) => (int) $v)->filter()->all();

        DB::transaction(function () use ($review, $data, $imagePaths, $deleteIds): void {
            $review->update($data);

            if ($deleteIds !== []) {
                $toDelete = $review->images()->whereIn('id', $deleteIds)->get();
                foreach ($toDelete as $img) {
                    $this->images->delete($img->path);
                    $img->delete();
                }
            }

            $startOrder = (int) $review->images()->max('sort_order') + 1;
            foreach ($imagePaths as $idx => $path) {
                ReviewImage::query()->create([
                    'review_id' => $review->id,
                    'path' => $path,
                    'sort_order' => $startOrder + $idx,
                ]);
            }
        });

        return redirect()->route('admin.reviews.index')->with('success', 'Review updated.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->load('images');
        DB::transaction(function () use ($review): void {
            foreach ($review->images as $img) {
                $this->images->delete($img->path);
            }
            $review->delete();
        });

        return redirect()->route('admin.reviews.index')->with('success', 'Review deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Review $review = null): array
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'customer_name' => 'required|string|max:160',
            'customer_email' => 'required|email|max:190',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'content' => 'required|string|max:5000',
            'status' => 'required|in:'.implode(',', Review::STATUSES),
            'images' => 'nullable|array|max:5',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'integer',
        ];

        $validated = $request->validate($rules);

        return [
            'product_id' => (int) $validated['product_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'order_id' => $review?->order_id,
            'order_item_id' => $review?->order_item_id,
            'user_id' => $review?->user_id,
        ];
    }
}
