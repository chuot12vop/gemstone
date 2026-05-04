<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'name', 'slug', 'description', 'meta_title', 'meta_description', 'sort_order',
    ];

    /** @return HasMany<Product> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
