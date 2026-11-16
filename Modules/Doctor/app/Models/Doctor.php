<?php

namespace Modules\Doctor\app\Models;

use App\Models\DoctorSocialMedia;
use App\Models\MeetingHistory;
use App\Models\Message;
use App\Models\ZoomCredential;
use App\Models\ZoomMeeting;
use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Appointment\app\Models\Appointment;
use Modules\Doctor\app\Enums\DoctorStatus;
use Modules\Doctor\app\Models\DoctorSubscription;
use Modules\Leave\app\Models\Leave;
use Modules\Schedule\app\Models\Schedule;

class Doctor extends Authenticatable {
    use HasApiTokens, HasFactory;

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
        'password',
        'phone',
        'whatsapp',
        'forget_password_token',
        'email_verified_at',
        'email_verified_token',
        'registration_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password',
        'remember_token', 'forget_password_token',
        'email_verified_at',
        'email_verified_token', 'updated_at', 'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    public function getDesignationsAttribute(): ?string {
        return $this->translation?->designations ?? $this->translation?->licensing_body;
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
        return $this->belongsToMany(Service::class, 'doctor_service', 'doctor_id', 'service_id');
    }
    public function schedules(): ?HasMany {
        return $this->hasMany(Schedule::class, 'doctor_id');
    }
    public function leaves(): ?HasMany {
        return $this->hasMany(Leave::class, 'doctor_id');
    }
    public function appointments(): ?HasMany {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function translation(): ?HasOne {
        return $this->hasOne(DoctorTranslation::class)->where('lang_code', getSessionLanguage());
    }

    public function getTranslation($code): ?DoctorTranslation {
        return $this->hasOne(DoctorTranslation::class)->where('lang_code', $code)->first();
    }

    public function translations(): ?HasMany {
        return $this->hasMany(DoctorTranslation::class, 'doctor_id');
    }

    public function subscriptions(): ?HasMany {
        return $this->hasMany(DoctorSubscription::class, 'doctor_id');
    }

    public function providerProfile(): MorphOne {
        return $this->morphOne(ProviderProfile::class, 'provider');
    }

    public function latestSubscription(): HasOne {
        return $this->hasOne(DoctorSubscription::class, 'doctor_id')->latestOfMany();
    }

    public function zoom_credentials(): ?HasOne {
        return $this->hasOne(ZoomCredential::class, 'doctor_id')->select('doctor_id', 'zoom_account_id', 'zoom_api_key', 'zoom_api_secret');
    }
    public function accountID() {
        return $this->zoom_credentials->zoom_account_id;
    }
    public function clientID(): ?string {
        return $this->zoom_credentials->zoom_api_key;
    }

    public function clientSecret() {
        return $this->zoom_credentials->zoom_api_secret;
    }

    public function meeting(): ?HasMany {
        return $this->hasMany(ZoomMeeting::class, 'doctor_id');
    }
    public function meeting_history(): ?HasMany {
        return $this->hasMany(MeetingHistory::class, 'doctor_id');
    }
    public function scopeActive($query) {
        return $query->where('status', DoctorStatus::ACTIVE->value);
    }
    public function scopeInactive($query) {
        return $query->where('status', DoctorStatus::INACTIVE->value);
    }
    public function scopePubliclyVisible($query) {
        return $query->active()->whereNotNull('email_verified_at');
    }
    public function scopeHomepage($query) {
        return $query->where('show_homepage', 1);
    }
    public function scopeVerify($query) {
        return $query->where('email_verified_at', '!=', null);
    }
    public function scopeLicenseVerified($query) {
        return $query->whereExists(function ($document) {
            $document->selectRaw('1')->from('provider_documents')->whereColumn('provider_documents.provider_id', 'doctors.id')->where('provider_documents.provider_type', 'doctor')->where('provider_documents.status', 'approved');
        });
    }
    public function scopePaid($query) {
        return $query->where('fee', '!=', 0);
    }
    public function messages(): ?HasMany {
        return $this->hasMany(Message::class, 'doctor_id');
    }
    public function socialMedia(): ?HasMany {
        return $this->hasMany(DoctorSocialMedia::class, 'doctor_id');
    }
}
