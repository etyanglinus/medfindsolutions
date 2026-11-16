<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Doctor\app\Models\Doctor;

class ZoomCredential extends Model {
    use HasFactory;
    protected $fillable = ['doctor_id', 'zoom_account_id','zoom_api_key', 'zoom_api_secret'];
    protected $hidden = ['updated_at','created_at'];
    public function doctor(): ?BelongsTo {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
