<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryAdminController extends Controller
{
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
        Category::query()->create($this->validated($request));

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
        $category->update($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
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
