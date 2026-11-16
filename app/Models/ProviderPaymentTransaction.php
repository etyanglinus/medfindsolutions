<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderPaymentTransaction extends Model
{
    use HasFactory;

    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUND_PENDING = 'refund_pending';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'provider_profile_id',
        'provider_subscription_id',
        'gateway',
        'gateway_reference',
        'idempotency_key',
        'amount',
        'currency',
        'status',
        'paid_at',
        'failure_reason',
        'gateway_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'gateway_payload' => 'array',
    ];

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class)->withTrashed();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ProviderSubscription::class, 'provider_subscription_id');
    }
}
