<?php

namespace Modules\HealthcareInstitution\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthcareCourse extends Model
{
    use HasFactory;

    protected $fillable = ['healthcare_institution_id', 'name', 'slug', 'description', 'intake_information', 'fees', 'application_information', 'approval_status', 'admin_note', 'is_featured', 'intake_starts_at', 'intake_ends_at'];
    protected $casts = ['is_featured' => 'boolean', 'intake_starts_at' => 'date', 'intake_ends_at' => 'date'];

    public function institution(): BelongsTo { return $this->belongsTo(HealthcareInstitution::class, 'healthcare_institution_id'); }
    public function getRouteKeyName(): string { return 'id'; }
}
