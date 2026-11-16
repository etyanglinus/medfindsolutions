<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderSubscription extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'provider_profile_id',
        'subscription_package_id',
        'plan_code',
        'package_name',
        'amount',
        'currency',
        'duration_months',
        'status',
        'starts_at',
        'expires_at',
        'approved_at',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class)->withTrashed();
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(\Modules\Doctor\app\Models\SubscriptionPackage::class, 'subscription_package_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ProviderPaymentTransaction::class);
    }
}
