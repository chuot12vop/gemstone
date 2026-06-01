<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Services\PublicImageStore;
use App\Support\HomeSectionSettings;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class InterfaceAdminController extends Controller
{
    private const PUBLIC_STORAGE_PREFIX = '/storage/';

    private const SLIDES_KEY = 'home_banner_slides';

    private const SECTION_IMAGE_PREFIX = 'settings/home-sections/';

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
            'sectionStyles' => HomeSectionSettings::resolveForForm(),
            'sectionLabels' => HomeSectionSettings::SECTION_LABELS,
            'sectionKeys' => HomeSectionSettings::SECTION_KEYS,
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
            'slides.*.existing_image_mobile' => 'nullable|string|max:512',
            'slides.*.image_mobile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'slides.*.category_id' => 'nullable|integer|exists:categories,id',
            'sections' => 'nullable|array',
            'sections.*.background_color' => ['nullable', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'sections.*.existing_background_image' => 'nullable|string|max:512',
            'sections.*.background_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'sections.*.remove_background_image' => 'nullable|boolean',
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
            $existingMobile = trim((string) ($row['existing_image_mobile'] ?? ''));
            $fileMobile = $request->file('slides.'.$index.'.image_mobile');

            $imagePath = $this->resolveSlideImagePath($file, $existing);
            $imageMobilePath = $this->resolveSlideImagePath($fileMobile, $existingMobile);

            if ($imagePath === null || $imagePath === '') {
                continue;
            }

            $slidePayload = [
                'image' => $imagePath,
                'title' => $title !== '' ? $title : 'Welcome',
                'content' => $content,
            ];
            if ($imageMobilePath !== null && $imageMobilePath !== '') {
                $slidePayload['image_mobile'] = $imageMobilePath;
            }
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

        $this->saveSectionStyles($request, $validated['sections'] ?? []);

        return redirect()->route('admin.interface.index')->with('success', 'Home interface updated.');
    }

    /**
     * @return list<array{image: string, image_mobile: string, title: string, content: string, category_id: int|null}>
     */
    private function slidesForForm(): array
    {
        $slides = $this->decodedSlidesFromDb();
        if ($slides === []) {
            return [['image' => '', 'image_mobile' => '', 'title' => '', 'content' => '', 'category_id' => null]];
        }

        return $slides;
    }

    /**
     * @return list<array{image: string, image_mobile: string, title: string, content: string, category_id: int|null}>
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
                'image_mobile' => trim((string) ($item['image_mobile'] ?? '')),
                'title' => (string) ($item['title'] ?? ''),
                'content' => (string) ($item['content'] ?? ''),
                'category_id' => $cid > 0 ? $cid : null,
            ];
        }

        return $out;
    }

    /**
     * @param list<array{image?: string, image_mobile?: string}> $slides
     * @return list<string>
     */
    private function collectImagePaths(array $slides): array
    {
        $paths = [];
        foreach ($slides as $slide) {
            foreach (['image', 'image_mobile'] as $key) {
                $path = trim((string) ($slide[$key] ?? ''));
                if ($path !== '') {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function resolveSlideImagePath(?UploadedFile $file, string $existing): ?string
    {
        if ($file instanceof UploadedFile) {
            return $this->images->store($file, 'settings/banner-slides', asWebp: true);
        }

        return $existing !== '' ? $existing : null;
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

    /**
     * @param  array<string, array{background_color?: string, existing_background_image?: string, remove_background_image?: bool}>  $inputRows
     */
    private function saveSectionStyles(Request $request, array $inputRows): void
    {
        $oldStyles = HomeSectionSettings::resolveForForm();
        $newStyles = [];

        foreach (HomeSectionSettings::SECTION_KEYS as $key) {
            $row = $inputRows[$key] ?? [];
            $existing = trim((string) ($row['existing_background_image'] ?? ($oldStyles[$key]['background_image'] ?? '')));
            $remove = filter_var($row['remove_background_image'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $file = $request->file('sections.'.$key.'.background_image');

            $imagePath = $existing;
            if ($remove) {
                $imagePath = '';
            }
            if ($file instanceof UploadedFile) {
                $imagePath = $this->images->store($file, 'settings/home-sections', asWebp: true) ?? '';
            }

            $newStyles[$key] = [
                'background_color' => (string) ($row['background_color'] ?? ($oldStyles[$key]['background_color'] ?? '#ffffff')),
                'background_image' => $imagePath,
            ];
        }

        $oldPaths = $this->collectSectionImagePaths($oldStyles);
        $newPaths = $this->collectSectionImagePaths($newStyles);
        foreach (array_diff($oldPaths, $newPaths) as $removed) {
            $this->deleteSectionImagePath($removed);
        }

        HomeSectionSettings::store($newStyles);
    }

    /**
     * @param  array<string, array{background_image?: string}>  $styles
     * @return list<string>
     */
    private function collectSectionImagePaths(array $styles): array
    {
        $paths = [];
        foreach ($styles as $style) {
            $path = trim((string) ($style['background_image'] ?? ''));
            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }

    private function deleteSectionImagePath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $relativePath = Str::startsWith($path, self::PUBLIC_STORAGE_PREFIX)
            ? Str::after($path, self::PUBLIC_STORAGE_PREFIX)
            : ltrim($path, '/');

        if ($relativePath !== '' && Str::startsWith($relativePath, self::SECTION_IMAGE_PREFIX)) {
            $this->images->delete($path);
        }
    }
}
