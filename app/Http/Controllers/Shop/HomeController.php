<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Support\AboutPageSettings;
use App\Support\ShopFrontSettings;
use App\Support\WelcomePopupSettings;
use App\Services\CurrencyService;
use App\Support\PublicAssetUrl;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $defaults = [
            'site_name' => config('app.name'),
            'site_logo' => '',
            'security_policy' => '',
            'privacy_policy' => '',
            'return_policy' => '',
            'terms_of_service' => '',
            'retail_policy' => '',
        ];

        $storedSettings = Setting::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->toArray();

        foreach ($storedSettings as $key => $value) {
            if (array_key_exists($key, $defaults) && $value !== null) {
                $defaults[$key] = (string) $value;
            }
        }

        $defaults['site_logo'] = PublicAssetUrl::to($defaults['site_logo']);

        $bannerSlides = $this->resolvedBannerSlides();

        $homeNewProducts = Product::query()
            ->where('is_active', true)
            ->with('category')
            ->latest('id')
            ->limit(6)
            ->get();
        
        $bestSellers = Category::query()
            ->where('slug', 'Best-Sellers')
            ->first();
        if ($bestSellers) {
            $homeBestSellers = Product::query()
                ->where('is_active', true)
                ->where('category_id', $bestSellers->id)
                ->with('category')
                ->limit(6)
                ->get();
        } else {
            $homeBestSellers = [];
        }

        $homeCollections = Category::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(6)
            ->get();

        $homeCertificates = Certificate::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $homeJournalPosts = Post::query()
            ->where('is_active', 1)
            ->orderByDesc('published_at')
            ->orderBy('sort_order')
            ->get();

        $homeReviews = Review::query()
            ->approved()
            ->whereHas('images')
            ->with('images')
            ->latest()
            ->take(10)
            ->get();

        return view('shop.home', [
            'siteSettings' => $defaults,
            'shopFront' => ShopFrontSettings::resolve(),
            'bannerSlides' => $bannerSlides,
            'title' => 'Gemstone Jewelry & Feng Shui — Taichi-inspired wellness',
            'metaDescription' => 'Premium gemstone jewelry for balance, luck, and intention. Ethically sourced, handcrafted for the US market.',
            'homeNewProducts' => $homeNewProducts,
            'homeBestSellers' => $homeBestSellers,
            'homeCollections' => $homeCollections,
            'homeCertificates' => $homeCertificates,
            'homeJournalPosts' => $homeJournalPosts,
            'homeReviews' => $homeReviews,
            'about' => AboutPageSettings::resolve(),
            'welcomePopup' => WelcomePopupSettings::resolve(),
            'currency' => app(CurrencyService::class),
        ]);
    }

    /**
     * @return list<array{image: string, title: string, content: string, category_id: int|null, cta_url: string}>
     */
    private function resolvedBannerSlides(): array
    {
        $raw = Setting::query()->where('key', 'home_banner_slides')->value('value');
        $slides = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $image = trim((string) ($item['image'] ?? ''));
                    if ($image === '') {
                        continue;
                    }
                    $cid = isset($item['category_id']) ? (int) $item['category_id'] : 0;
                    $slides[] = [
                        'image' => PublicAssetUrl::to($image),
                        'title' => (string) ($item['title'] ?? ''),
                        'content' => (string) ($item['content'] ?? ''),
                        'category_id' => $cid > 0 ? $cid : null,
                    ];
                }
            }
        }

        if ($slides !== []) {
            return $this->withBannerCtaUrls($slides);
        }

        $legacy = Setting::query()->where('key', 'home_banner')->value('value');
        $legacy = is_string($legacy) ? trim($legacy) : '';
        if ($legacy !== '') {
            return $this->withBannerCtaUrls([[
                'image' => PublicAssetUrl::to($legacy),
                'title' => 'Vitality & Balance',
                'content' => 'Elevate your energy with naturally selected gemstone bracelets and handcrafted feng shui pieces.',
                'category_id' => null,
            ]]);
        }

        return $this->withBannerCtaUrls([[
            'image' => 'https://taichigemstone.com/cdn/shop/files/Gemini_Generated_Image_7ja7m27ja7m27ja7.png?v=1773133793&width=1400',
            'title' => 'Vitality & Balance',
            'content' => 'Elevate your energy with naturally selected gemstone bracelets and handcrafted feng shui pieces.',
            'category_id' => null,
        ]]);
    }

    /**
     * @param list<array{image: string, title: string, content: string, category_id: int|null}> $slides
     * @return list<array{image: string, title: string, content: string, category_id: int|null, cta_url: string}>
     */
    private function withBannerCtaUrls(array $slides): array
    {
        $ids = [];
        foreach ($slides as $s) {
            $id = (int) ($s['category_id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        $slugById = $ids === []
            ? collect()
            : Category::query()->whereIn('id', $ids)->pluck('slug', 'id');

        foreach ($slides as $k => $slide) {
            $cid = (int) ($slide['category_id'] ?? 0);
            if ($cid > 0 && $slugById->has($cid)) {
                $slides[$k]['cta_url'] = route('shop.catalog.category', $slugById[$cid]);
            } else {
                $slides[$k]['cta_url'] = route('shop.products.index');
                $slides[$k]['category_id'] = null;
            }
        }

        return $slides;
    }
}
