<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\PublicImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryAdminController extends Controller
{
    private PublicImageStore $images;

    public function __construct(PublicImageStore $images)
    {
        $this->images = $images;
    }

    public function index()
    {
        return view('admin.categories.index', [
            'title' => 'Categories',
            'breadcrumbs' => [
                ['label' => 'Categories'],
            ],
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.categories.form', [
            'title' => 'New category',
            'breadcrumbs' => [
                ['label' => 'Categories', 'url' => route('admin.categories.index')],
                ['label' => 'New'],
            ],
            'category' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $imageUrl = $this->images->store($request->file('image'), 'categories', asWebp: true);
        if ($imageUrl !== null) {
            $data['image'] = $imageUrl;
        }
        $catalogBannerUrl = $this->images->store($request->file('catalog_banner'), 'categories/catalog-banners', asWebp: true);
        if ($catalogBannerUrl !== null) {
            $data['catalog_banner'] = $catalogBannerUrl;
        }

        Category::query()->create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'title' => 'Edit category',
            'breadcrumbs' => [
                ['label' => 'Categories', 'url' => route('admin.categories.index')],
                ['label' => $category->name],
            ],
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request);
        $imageUrl = $this->images->store($request->file('image'), 'categories', asWebp: true);
        if ($imageUrl !== null) {
            $this->images->delete($category->image);
            $data['image'] = $imageUrl;
        }
        $catalogBannerUrl = $this->images->store($request->file('catalog_banner'), 'categories/catalog-banners', asWebp: true);
        if ($catalogBannerUrl !== null) {
            $this->images->delete($category->catalog_banner);
            $data['catalog_banner'] = $catalogBannerUrl;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')->with('error', 'Cannot delete a category that still has products. Reassign or delete those products first.');
        }

        $this->images->delete($category->image);
        $this->images->delete($category->catalog_banner);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'catalog_banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'name' => 'required|string|max:160',
            'slug' => 'nullable|string|max:160',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:190',
            'meta_description' => 'nullable|string|max:320',
        ]);

        $slug = $validated['slug'] ?? '';
        if ($slug === '') {
            $slug = Str::slug($validated['name']);
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug ?: 'category',
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ];
    }
}
