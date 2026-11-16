<?php

namespace Modules\HealthcareInstitution\app\Models;

use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthcareInstitution extends Authenticatable
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use HasFactory;

    protected $fillable = ['provider_profile_id', 'name', 'slug', 'institution_type', 'ownership', 'official_email', 'phone', 'contact_person', 'website', 'whatsapp', 'logo', 'cover_image', 'about', 'location', 'facilities', 'practical_training', 'accommodation', 'international_pathways', 'registration_number', 'accreditation_body', 'accreditation_expires_at', 'application_url', 'location_map_url', 'intake_campaign_title', 'intake_campaign_ends_at', 'password', 'registration_token', 'email_verified_at', 'email_verified_token'];

    protected $hidden = ['password', 'remember_token', 'registration_token', 'email_verified_token'];

    protected $casts = ['password' => 'hashed', 'email_verified_at' => 'datetime', 'accreditation_expires_at' => 'date', 'intake_campaign_ends_at' => 'date'];

    public function providerProfile(): BelongsTo { return $this->belongsTo(ProviderProfile::class); }
    public function documents(): HasMany { return $this->hasMany(\Modules\Doctor\app\Models\ProviderDocument::class, 'provider_id')->where('provider_type', 'school'); }
    public function courses(): HasMany { return $this->hasMany(HealthcareCourse::class); }
    public function enquiries(): HasMany { return $this->hasMany(HealthcareInstitutionEnquiry::class); }

    public function scopePublished($query)
    {
        return $query->whereHas('providerProfile', fn ($profile) => $profile
            ->where('status', ProviderProfile::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->whereHas('subscriptions', fn ($subscription) => $subscription
                ->where('status', \App\Models\ProviderSubscription::STATUS_APPROVED)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))));
    }
}
