<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderProfile extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    public const TYPE_SPECIALIST = 'doctor';
    public const TYPE_FACILITY = 'clinic';
    public const TYPE_SCHOOL = 'school';
    public const TYPE_SUPPLIER = 'supplier';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_CHANGES_REQUIRED = 'changes_required';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SUSPENDED = 'suspended';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_REJECTED = 'rejected';

    protected $fillable = [
        'provider_type',
        'provider_id',
        'status',
        'verification_status',
        'published_at',
        'verified_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function provider(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProviderSubscription::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(ProviderPaymentTransaction::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED)
            ->whereNotNull('verified_at');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at !== null;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED && $this->verified_at !== null;
    }
}
