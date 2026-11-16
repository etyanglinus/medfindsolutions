<?php

namespace Modules\Doctor\app\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Appointment\app\Models\Appointment;

class Prescribe extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['appointment_id', 'medicine_type', 'medicine_name', 'dosage', 'number_of_day', 'time'];
    public function appointment() {
        return $this->belongsTo(Appointment::class);
    }
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date) {
        return $date->format('Y-m-d H:i:s');
    }
}
