<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorTranslation extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'doctor_id',
        'lang_code',
        'designations',
        'licensing_body',
        'accepted_insurance',
        'about',
        'address',
        'educations',
        'experience',
        'qualifications',
        'seo_title',
        'seo_description',
    ];
    protected $hidden = ['updated_at','created_at'];

    public function doctor(): ?BelongsTo {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
