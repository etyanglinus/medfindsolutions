<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionCoupon extends Model
{
    protected $fillable = ['code', 'provider_types', 'plan_codes', 'eligible_email', 'discount_type', 'discount_value', 'usage_limit', 'usage_count', 'starts_at', 'expires_at', 'is_active'];

    protected $casts = ['provider_types' => 'array', 'plan_codes' => 'array', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'is_active' => 'boolean'];
}
