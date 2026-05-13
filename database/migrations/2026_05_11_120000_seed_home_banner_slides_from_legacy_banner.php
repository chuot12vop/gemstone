<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasSlides = DB::table('settings')->where('key', 'home_banner_slides')->exists();
        if ($hasSlides) {
            return;
        }

        $banner = DB::table('settings')->where('key', 'home_banner')->value('value');
        $banner = $banner !== null ? trim((string) $banner) : '';
        if ($banner === '') {
            return;
        }

        $slides = [[
            'image' => $banner,
            'title' => 'Vitality & Balance',
            'content' => 'Elevate your energy with naturally selected gemstone bracelets and handcrafted feng shui pieces.',
        ]];

        DB::table('settings')->insert([
            'key' => 'home_banner_slides',
            'value' => json_encode($slides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'home_banner_slides')->delete();
    }
};
