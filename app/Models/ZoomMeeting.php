<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Doctor\app\Models\Doctor;

class ZoomMeeting extends Model {
    use HasFactory;
    protected $fillable = [
        'doctor_id', 'topic', 'start_time', 'duration', 'meeting_id', 'password', 'join_url',
    ];
    protected $hidden = ['doctor_id', 'updated_at', 'created_at'];

    public function doctor() {
        return $this->belongsTo(Doctor::class);
    }
    public function meeting_history(): ?HasMany {
        return $this->hasMany(MeetingHistory::class, 'meeting_id');
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
