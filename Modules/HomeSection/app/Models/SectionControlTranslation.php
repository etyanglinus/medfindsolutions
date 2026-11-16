<?php

namespace Modules\HomeSection\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionControlTranslation extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lang_code',
        'section_control_id',
        'work_first_heading',
        'work_second_heading',
        'work_description',
        'service_first_heading',
        'service_second_heading',
        'service_description',
        'department_first_heading',
        'department_second_heading',
        'department_description',
        'patient_first_heading',
        'patient_second_heading',
        'patient_description',
        'doctor_first_heading',
        'doctor_second_heading',
        'doctor_description',
        'blog_first_heading',
        'blog_second_heading',
        'blog_description',
    ];

    public function section_control(): ?BelongsTo {
        return $this->belongsTo(SectionControl::class, 'section_control_id');
    }
}
