<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\PublicAssetUrl;
use Illuminate\View\View;

class PostController extends Controller
{
    public function show(Post $post): View
    {
        if (! $post->is_active || ($post->published_at && $post->published_at->isFuture())) {
            abort(404);
        }

        return view('shop.posts.show', [
            'title' => $post->title,
            'metaDescription' => $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body), 160),
            'post' => $post,
            'postImage' => PublicAssetUrl::to($post->image),
        ]);
    }
}
