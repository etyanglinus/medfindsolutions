<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\ProviderProfile;

class Clinic extends Authenticatable {
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'slug',
        'specialty',
        'email',
        'phone',
        'consultation_fee',
        'years_of_experience',
        'whatsapp',
        'logo',
        'description',
        'address',
        'educations',
        'experience',
        'qualifications',
        'seo_title',
        'seo_description',
        'licensing_body',
        'equipment',
        'featured_services',
        'location',
        'google_maps_link',
        'insurance_accepted',
        'operating_hours',
        'emergency_available',
        'reviews_enabled',
        'status',
        'password',
        'registration_token',
        'email_verified_at',
        'email_verified_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'  => 'datetime',
        'emergency_available' => 'boolean',
        'reviews_enabled'    => 'boolean',
    ];

    public function subscriptions(): HasMany {
        return $this->hasMany(ClinicSubscription::class, 'clinic_id');
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
        // Pivot created via DashboardController::updateProfile services sync.
        // Table may be named clinic_service (created via seeder/tinker, no migration file).
        return $this->belongsToMany(Service::class, 'clinic_service', 'clinic_id', 'service_id');
    }

    public function providerProfile(): MorphOne {
        return $this->morphOne(ProviderProfile::class, 'provider');
    }

    public function latestSubscription(): HasOne {
        return $this->hasOne(ClinicSubscription::class, 'clinic_id')->latestOfMany();
    }

    public function galleryImages(): HasMany {
        return $this->hasMany(ClinicGalleryImage::class);
    }

    public function branches(): HasMany {
        return $this->hasMany(ClinicBranch::class);
    }

    public function scopeActive($query) {
        return $query->where('status', 1);
    }
    public function scopePubliclyVisible($query) {
        return $query->active()->whereNotNull('email_verified_at');
    }
    public function scopeLicenseVerified($query) {
        return $query->whereExists(function ($document) {
            $document->selectRaw('1')->from('provider_documents')->whereColumn('provider_documents.provider_id', 'clinics.id')->where('provider_documents.provider_type', 'clinic')->where('provider_documents.status', 'approved');
        });
    }
}
