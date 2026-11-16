<?php

namespace Modules\PaymentWithdraw\app\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\app\Models\Doctor;

class WithdrawRequest extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['doctor_id', 'withdraw_method_id', 'total_amount', 'withdraw_amount', 'withdraw_charge', 'account_info', 'status', 'approved_date'];

    public function doctor() {
        return $this->belongsTo(Doctor::class)->select('id', 'name', 'email', 'image');
    }
    public function withdraw_method() {
        return $this->belongsTo(WithdrawMethod::class);
    }
    public function scopeActive($query) {
        return $query->where('status', 'approved');
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
