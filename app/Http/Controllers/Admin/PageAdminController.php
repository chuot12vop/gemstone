<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function index()
    {
        return view('admin.pages.index', [
            'title' => 'Pages',
            'breadcrumbs' => [
                ['label' => 'Pages'],
            ],
            'pages' => Page::query()->orderBy('sort_order')->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.pages.form', [
            'title' => 'New page',
            'breadcrumbs' => [
                ['label' => 'Pages', 'url' => route('admin.pages.index')],
                ['label' => 'New'],
            ],
            'page' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $imageUrl = $this->storeImage($request->file('image'), 'pages');
        if ($imageUrl !== null) {
            $data['image'] = $imageUrl;
        }

        Page::query()->create($data);

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', [
            'title' => 'Edit page',
            'breadcrumbs' => [
                ['label' => 'Pages', 'url' => route('admin.pages.index')],
                ['label' => $page->title],
            ],
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->validated($request, $page);
        $imageUrl = $this->storeImage($request->file('image'), 'pages');
        if ($imageUrl !== null) {
            $this->deletePublicPath($page->image);
            $data['image'] = $imageUrl;
        }

        $page->update($data);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        $this->deletePublicPath($page->image);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => [
                'nullable',
                'string',
                'max:200',
                Rule::unique('pages', 'slug')->ignore($page?->id),
            ],
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = trim((string) ($validated['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($validated['title']);
        }

        return [
            'title' => $validated['title'],
            'slug' => $slug ?: 'page',
            'description' => $validated['description'] ?? null,
            'content' => $validated['content'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function storeImage(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }

        $path = $file->storeAs(trim($directory, '/'), Str::uuid()->toString().'.'.$extension, 'public');

        return is_string($path) && $path !== '' ? self::PUBLIC_STORAGE_PREFIX.$path : null;
    }

    private function deletePublicPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relativePath = Str::startsWith($path, self::PUBLIC_STORAGE_PREFIX)
            ? Str::after($path, self::PUBLIC_STORAGE_PREFIX)
            : ltrim($path, '/');

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
