<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    protected $fillable = ['provider_type', 'code', 'name', 'tagline', 'price', 'duration_months', 'features', 'entitlements', 'is_active'];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'entitlements' => 'array',
        'is_active' => 'boolean',
    ];
}
