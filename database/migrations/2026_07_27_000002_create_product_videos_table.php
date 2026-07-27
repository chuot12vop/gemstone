<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_videos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path', 512);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'sort_order']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('show_at_home')->default(false)->after('is_active');
        });

        if (Schema::hasColumn('products', 'video_path')) {
            $now = now();
            DB::table('products')
                ->whereNotNull('video_path')
                ->where('video_path', '!=', '')
                ->orderBy('id')
                ->get(['id', 'video_path'])
                ->each(function ($product) use ($now): void {
                    DB::table('product_videos')->insert([
                        'product_id' => $product->id,
                        'path' => $product->video_path,
                        'sort_order' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });

            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('video_path');
            });
        }

        if (Schema::hasTable('settings')) {
            $legacyIds = json_decode((string) DB::table('settings')->where('key', 'home_video_product_ids')->value('value'), true);
            $legacyIds = array_values(array_filter(array_map('intval', is_array($legacyIds) ? $legacyIds : [])));
            if ($legacyIds !== []) {
                DB::table('products')->whereIn('id', $legacyIds)->where('is_active', true)
                    ->whereExists(fn ($query) => $query->selectRaw('1')->from('product_videos')->whereColumn('product_videos.product_id', 'products.id'))
                    ->update(['show_at_home' => true]);
            }
            DB::table('settings')->where('key', 'home_video_product_ids')->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            $ids = DB::table('products')->where('show_at_home', true)->orderBy('name')->orderBy('id')->pluck('id')->all();
            DB::table('settings')->updateOrInsert(
                ['key' => 'home_video_product_ids'],
                ['value' => json_encode($ids, JSON_UNESCAPED_SLASHES)]
            );
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->string('video_path', 512)->nullable()->after('thumbnail');
        });

        DB::table('product_videos')
            ->where('sort_order', 0)
            ->orderBy('id')
            ->get(['product_id', 'path'])
            ->each(fn ($video) => DB::table('products')->where('id', $video->product_id)->update(['video_path' => $video->path]));

        Schema::dropIfExists('product_videos');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('show_at_home');
        });
    }
};
