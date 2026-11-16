<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSubscription extends Model {
    use HasFactory;

    protected $fillable = [
        'doctor_id',
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

    public function doctor(): BelongsTo {
        return $this->belongsTo(Doctor::class);
    }

    public function isActive(): bool {
        return $this->status === 'approved' && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function entitlement(string $name, mixed $default = null): mixed {
        $entitlements = $this->entitlements;
        // Legacy payments predate entitlement snapshots. Preserve explicit snapshots.
        if (! is_array($entitlements) || $entitlements === []) {
            $code = $this->plan_code ?: strtolower(trim($this->package_name ?? ''));
            $plans = app(\Modules\Doctor\app\Services\SubscriptionPlanService::class);
            $plan = $plans->find('doctor', $code)
                ?? collect($plans->defaults('doctor'))->firstWhere('id', $code);
            $entitlements = $plan['entitlements'] ?? [];
        }
        return data_get($entitlements, $name, $default);
    }

    public function hasEntitlement(string $name): bool {
        return (bool) $this->entitlement($name);
    }
}
