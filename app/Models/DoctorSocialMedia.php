<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Doctor\app\Models\Doctor;

class DoctorSocialMedia extends Model {
    use HasFactory;
    protected $fillable = ['doctor_id', 'link', 'icon', 'status'];
    public function doctor(): ?BelongsTo {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function scopeActive($query) {
        return $query->where('status', 1);
    }
}
