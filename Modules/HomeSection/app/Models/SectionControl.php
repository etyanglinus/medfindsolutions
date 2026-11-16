<?php

namespace Modules\HomeSection\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SectionControl extends Model {
    use HasFactory;

    protected $fillable = [
        'feature_how_many',
        'feature_status',
        'work_how_many',
        'work_status',
        'service_how_many',
        'service_status',
        'department_how_many',
        'department_status',
        'patient_how_many',
        'patient_status',
        'doctor_how_many',
        'doctor_status',
        'blog_how_many',
        'blog_status',
    ];

    public function getWorkFirstHeadingAttribute(): ?string {
        return $this->translation?->work_first_heading;
    }
    public function getWorkSecondHeadingAttribute(): ?string {
        return $this->translation?->work_second_heading;
    }
    public function getWorkDescriptionAttribute(): ?string {
        return $this->translation?->work_description;
    }
    public function getServiceFirstHeadingAttribute(): ?string {
        return $this->translation?->service_first_heading;
    }
    public function getServiceSecondHeadingAttribute(): ?string {
        return $this->translation?->service_second_heading;
    }
    public function getServiceDescriptionAttribute(): ?string {
        return $this->translation?->service_description;
    }
    public function getDepartmentFirstHeadingAttribute(): ?string {
        return $this->translation?->department_first_heading;
    }
    public function getDepartmentSecondHeadingAttribute(): ?string {
        return $this->translation?->department_second_heading;
    }
    public function getDepartmentDescriptionAttribute(): ?string {
        return $this->translation?->department_description;
    }
    public function getPatientFirstHeadingAttribute(): ?string {
        return $this->translation?->patient_first_heading;
    }
    public function getPatientSecondHeadingAttribute(): ?string {
        return $this->translation?->patient_second_heading;
    }
    public function getPatientDescriptionAttribute(): ?string {
        return $this->translation?->patient_description;
    }
    public function getDoctorFirstHeadingAttribute(): ?string {
        return $this->translation?->doctor_first_heading;
    }
    public function getDoctorSecondHeadingAttribute(): ?string {
        return $this->translation?->doctor_second_heading;
    }
    public function getDoctorDescriptionAttribute(): ?string {
        return $this->translation?->doctor_description;
    }
    public function getBlogFirstHeadingAttribute(): ?string {
        return $this->translation?->blog_first_heading;
    }
    public function getBlogSecondHeadingAttribute(): ?string {
        return $this->translation?->blog_second_heading;
    }
    public function getBlogDescriptionAttribute(): ?string {
        return $this->translation?->blog_description;
    }

    public function translation(): ?HasOne {
        return $this->hasOne(SectionControlTranslation::class)->where('lang_code', getSessionLanguage());
    }

    public function getTranslation($code): ?SectionControlTranslation {
        return $this->hasOne(SectionControlTranslation::class)->where('lang_code', $code)->first();
    }

    public function translations(): ?HasMany {
        return $this->hasMany(SectionControlTranslation::class, 'section_control_id');
    }
}
