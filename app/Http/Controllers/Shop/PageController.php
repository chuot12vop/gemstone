<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function about()
    {
        return view('shop.about', [
            'title' => 'About Gemstone — Heritage & intention',
            'metaDescription' => 'Learn how we bridge ancient feng shui wisdom with modern craftsmanship for US customers.',
        ]);
    }

    public function contact()
    {
        return view('shop.contact', [
            'title' => 'Contact — Gemstone support',
            'metaDescription' => 'Reach our team for orders and gemstone questions.',
        ]);
    }
}
