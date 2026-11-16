<?php

namespace Modules\Appointment\app\Models;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Day\app\Models\Day;
use Modules\Doctor\app\Models\Advice;
use Modules\Doctor\app\Models\Doctor;
use Modules\Doctor\app\Models\Prescribe;
use Modules\Order\app\Models\Order;
use Modules\Schedule\app\Models\Schedule;

class Appointment extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'order_id', 'doctor_id', 'user_id', 'day_id', 'schedule_id', 'date', 'appointment_fee_usd', 'appointment_fee', 'payable_currency', 'payment_status', 'payment_transaction_id', 'payment_method', 'payment_description', 'blood_pressure', 'pulse_rate', 'temperature', 'problem_description', 'habits', 'already_treated', 'status',
    ];
    protected $hidden = ['order_id', 'doctor_id', 'user_id', 'day_id', 'schedule_id', 'appointment_fee_usd', 'payment_description', 'updated_at', 'created_at'];

    public function day() {
        return $this->belongsTo(Day::class);
    }
    public function doctor() {
        return $this->belongsTo(Doctor::class);
    }
    public function schedule() {
        return $this->belongsTo(Schedule::class);
    }
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function prescribes() {
        return $this->hasMany(Prescribe::class);
    }

    public function advice() {
        return $this->hasOne(Advice::class);
    }

    public function order() {
        return $this->belongsTo(Order::class);
    }
    public function scopePaymentPending($query) {
        return $query->where('payment_status', 0);
    }
    public function scopePaymentSuccess($query) {
        return $query->where('payment_status', 1);
    }
    public function scopeTreated($query) {
        return $query->where('already_treated', 1);
    }
    public function scopeNotTreated($query) {
        return $query->where('already_treated', 0);
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
