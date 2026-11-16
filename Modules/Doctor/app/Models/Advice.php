<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Appointment\app\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Advice extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['appointment_id', 'advice', 'test_name', 'test_description'];
    public function appointment() {
        return $this->belongsTo(Appointment::class);
    }
}
