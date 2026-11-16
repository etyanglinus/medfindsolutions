<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicSubscription extends Model {
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'package_name',
        'plan_code',
        'package_price',
        'plan_duration',
        'entitlements',
        'payment_method',
        'bank_name',
        'account_number',
        'routing_number',
        'branch',
        'transaction_code',
        'status',
        'admin_note',
        'approved_at',
        'expires_at',
        'coupon_code', 'original_price', 'discount_amount',
    ];

    protected $casts = [
        'package_price' => 'decimal:2',
        'approved_at'   => 'datetime',
        'expires_at'    => 'datetime',
        'entitlements'  => 'array',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function clinic(): BelongsTo {
        return $this->belongsTo(Clinic::class);
    }

    public function isActive(): bool {
        return $this->status === 'approved' && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function hasEntitlement(string $name): bool {
        return (bool) $this->entitlement($name);
    }

    public function entitlement(string $name, mixed $default = null): mixed {
        return data_get($this->entitlements, $name, $default);
    }
}
