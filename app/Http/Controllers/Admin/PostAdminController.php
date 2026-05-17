<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    public function index()
    {
        return view('admin.posts.index', [
            'title' => 'News & articles',
            'breadcrumbs' => [
                ['label' => 'News & articles'],
            ],
            'posts' => Post::query()->orderByDesc('published_at')->orderBy('sort_order')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.posts.form', [
            'title' => 'New article',
            'breadcrumbs' => [
                ['label' => 'News & articles', 'url' => route('admin.posts.index')],
                ['label' => 'New'],
            ],
            'post' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $imageUrl = $this->storeImage($request->file('image'), 'posts');
        if ($imageUrl !== null) {
            $data['image'] = $imageUrl;
        }

        Post::query()->create($data);

        return redirect()->route('admin.posts.index')->with('success', 'Article created.');
    }

    public function edit(Post $post)
    {
        return view('admin.posts.form', [
            'title' => 'Edit article',
            'breadcrumbs' => [
                ['label' => 'News & articles', 'url' => route('admin.posts.index')],
                ['label' => $post->title],
            ],
            'post' => $post,
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post);
        $imageUrl = $this->storeImage($request->file('image'), 'posts');
        if ($imageUrl !== null) {
            $this->deletePublicPath($post->image);
            $data['image'] = $imageUrl;
        }

        $post->update($data);

        return redirect()->route('admin.posts.index')->with('success', 'Article updated.');
    }

    public function destroy(Post $post)
    {
        $this->deletePublicPath($post->image);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Article deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Post $post): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => [
                'nullable',
                'string',
                'max:200',
                Rule::unique('posts', 'slug')->ignore($post?->id),
            ],
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'published_at' => 'nullable|date',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $slug = trim((string) ($validated['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug($validated['title']);
        }

        return [
            'title' => $validated['title'],
            'slug' => $slug ?: 'article',
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'] ?? null,
            'published_at' => $validated['published_at'] ?? now(),
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
