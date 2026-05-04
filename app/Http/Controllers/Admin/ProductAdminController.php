<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Product::query()->with('category')->latest();
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        return view('admin.products.index', [
            'title' => 'Products',
            'breadcrumbs' => [
                ['label' => 'Products'],
            ],
            'products' => $query->get(),
            'q' => $q,
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'title' => 'New product',
            'breadcrumbs' => [
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => 'New'],
            ],
            'product' => null,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Product::query()->create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'title' => 'Edit product',
            'breadcrumbs' => [
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => $product->name],
            ],
            'product' => $product,
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($this->validated($request));

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200',
            'category_id' => 'required|exists:categories,id',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'price_usd' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:190',
            'meta_description' => 'nullable|string|max:320',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = $validated['slug'] ?? '';
        if ($slug === '') {
            $slug = Str::slug($validated['name']);
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug ?: 'item',
            'category_id' => (int) $validated['category_id'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price_usd' => (float) $validated['price_usd'],
            'stock' => (int) $validated['stock'],
            'image' => $validated['image'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
