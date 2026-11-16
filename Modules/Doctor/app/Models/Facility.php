<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Facility extends Model {
    use HasFactory;

    protected $fillable = [
        'department_id',
        'location_id',
        'name',
        'slug',
        'fee',
        'years_of_experience',
        'show_homepage',
        'status',
        'email',
        'phone',
        'image',
    ];

    protected $hidden = ['updated_at', 'created_at'];

    public function getLicensingBodyAttribute(): ?string {
        return $this->translation?->licensing_body;
    }

    public function getAboutAttribute(): ?string {
        return $this->translation?->about;
    }

    public function getAddressAttribute(): ?string {
        return $this->translation?->address;
    }

    public function getEducationsAttribute(): ?string {
        return $this->translation?->educations;
    }

    public function getExperienceAttribute(): ?string {
        return $this->translation?->experience;
    }

    public function getQualificationsAttribute(): ?string {
        return $this->translation?->qualifications;
    }

    public function getSeoTitleAttribute(): ?string {
        return $this->translation?->seo_title;
    }

    public function getSeoDescriptionAttribute(): ?string {
        return $this->translation?->seo_description;
    }

    public function getAcceptedInsuranceAttribute(): ?string {
        return $this->translation?->accepted_insurance;
    }

    public function department(): ?BelongsTo {
        return $this->belongsTo(Department::class);
    }

    public function location(): ?BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function services(): ?BelongsToMany {
        return $this->belongsToMany(Service::class, 'facility_service', 'facility_id', 'service_id');
    }

    public function translation(): ?HasOne {
        return $this->hasOne(FacilityTranslation::class)->where('lang_code', getSessionLanguage());
    }

    public function getTranslation($code): ?FacilityTranslation {
        return $this->hasOne(FacilityTranslation::class)->where('lang_code', $code)->first();
    }

    public function translations(): ?HasMany {
        return $this->hasMany(FacilityTranslation::class, 'facility_id');
    }

    public function scopeActive($query) {
        return $query->where('status', 1);
    }

    public function scopeInactive($query) {
        return $query->where('status', 0);
    }

    public function scopeHomepage($query) {
        return $query->where('show_homepage', 1);
    }
}
