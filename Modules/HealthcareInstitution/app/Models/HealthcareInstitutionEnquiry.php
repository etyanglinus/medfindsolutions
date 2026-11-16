<?php

namespace Modules\HealthcareInstitution\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthcareInstitutionEnquiry extends Model
{
    protected $fillable = ['healthcare_institution_id', 'healthcare_course_id', 'name', 'email', 'phone', 'organisation', 'message', 'status'];

    public function institution(): BelongsTo { return $this->belongsTo(HealthcareInstitution::class, 'healthcare_institution_id'); }
    public function course(): BelongsTo { return $this->belongsTo(HealthcareCourse::class, 'healthcare_course_id'); }
}
