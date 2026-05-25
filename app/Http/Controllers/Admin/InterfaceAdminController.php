<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Services\PublicImageStore;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class InterfaceAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    private const SLIDES_KEY = 'home_banner_slides';

    private PublicImageStore $images;

    public function __construct(PublicImageStore $images)
    {
        $this->images = $images;
    }

    public function index()
    {
        return view('admin.interface.index', [
            'title' => 'Interface',
            'breadcrumbs' => [
                ['label' => 'Interface'],
            ],
            'slides' => $this->slidesForForm(),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'slides' => 'nullable|array',
            'slides.*.title' => 'nullable|string|max:190',
            'slides.*.content' => 'nullable|string|max:4000',
            'slides.*.existing_image' => 'nullable|string|max:512',
            'slides.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'slides.*.category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $inputRows = $validated['slides'] ?? [];
        $oldSlides = $this->decodedSlidesFromDb();

        $newSlides = [];
        foreach ($inputRows as $index => $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $content = trim((string) ($row['content'] ?? ''));
            $categoryId = isset($row['category_id']) ? (int) $row['category_id'] : 0;
            if ($categoryId <= 0 || ! Category::query()->whereKey($categoryId)->exists()) {
                $categoryId = 0;
            }
            $existing = trim((string) ($row['existing_image'] ?? ''));
            $file = $request->file('slides.'.$index.'.image');

            $imagePath = null;
            if ($file instanceof UploadedFile) {
                $imagePath = $this->images->store($file, 'settings/banner-slides', asWebp: true);
            } elseif ($existing !== '') {
                $imagePath = $existing;
            }

            if ($imagePath === null || $imagePath === '') {
                continue;
            }

            $slidePayload = [
                'image' => $imagePath,
                'title' => $title !== '' ? $title : 'Welcome',
                'content' => $content,
            ];
            if ($categoryId > 0) {
                $slidePayload['category_id'] = $categoryId;
            }
            $newSlides[] = $slidePayload;
        }

        $oldPaths = $this->collectImagePaths($oldSlides);
        $newPaths = $this->collectImagePaths($newSlides);
        foreach (array_diff($oldPaths, $newPaths) as $removed) {
            $this->deleteBannerPath($removed);
        }

        Setting::query()->updateOrCreate(
            ['key' => self::SLIDES_KEY],
            ['value' => json_encode($newSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        return redirect()->route('admin.interface.index')->with('success', 'Home banner slides updated.');
    }

    /**
     * @return list<array{image: string, title: string, content: string, category_id: int|null}>
     */
    private function slidesForForm(): array
    {
        $slides = $this->decodedSlidesFromDb();
        if ($slides === []) {
            return [['image' => '', 'title' => '', 'content' => '', 'category_id' => null]];
        }

        return $slides;
    }

    /**
     * @return list<array{image: string, title: string, content: string, category_id: int|null}>
     */
    private function decodedSlidesFromDb(): array
    {
        $raw = Setting::query()->where('key', self::SLIDES_KEY)->value('value');
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            $image = trim((string) ($item['image'] ?? ''));
            if ($image === '') {
                continue;
            }
            $cid = isset($item['category_id']) ? (int) $item['category_id'] : 0;
            $out[] = [
                'image' => $image,
                'title' => (string) ($item['title'] ?? ''),
                'content' => (string) ($item['content'] ?? ''),
                'category_id' => $cid > 0 ? $cid : null,
            ];
        }

        return $out;
    }

    /**
     * @param list<array{image: string}> $slides
     * @return list<string>
     */
    private function collectImagePaths(array $slides): array
    {
        return array_values(array_filter(array_map(
            static fn (array $s): string => trim((string) ($s['image'] ?? '')),
            $slides
        )));
    }

    private function deleteBannerPath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relativePath = Str::startsWith($path, self::PUBLIC_STORAGE_PREFIX)
            ? Str::after($path, self::PUBLIC_STORAGE_PREFIX)
            : ltrim($path, '/');

        if ($relativePath !== '' && Str::startsWith($relativePath, 'settings/banner-slides/')) {
            $this->images->delete($path);
        }
    }
}
