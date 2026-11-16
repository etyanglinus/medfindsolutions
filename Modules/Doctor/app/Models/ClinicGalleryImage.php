<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicGalleryImage extends Model {
    protected $fillable = ['clinic_id', 'image_path', 'caption'];
}
