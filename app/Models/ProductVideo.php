<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideo extends Model
{
    protected $fillable = ['path', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    /** @return BelongsTo<Product, ProductVideo> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
