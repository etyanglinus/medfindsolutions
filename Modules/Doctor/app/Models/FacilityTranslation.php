<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityTranslation extends Model {
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'lang_code',
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

    protected $hidden = ['updated_at', 'created_at'];

    public function facility(): ?BelongsTo {
        return $this->belongsTo(Facility::class, 'facility_id');
    }
}
