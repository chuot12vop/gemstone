<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_READ, self::STATUS_ARCHIVED];

    protected $fillable = [
        'name',
        'phone',
        'address',
        'status',
        'ip',
        'user_agent',
    ];

    /**
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }
}
