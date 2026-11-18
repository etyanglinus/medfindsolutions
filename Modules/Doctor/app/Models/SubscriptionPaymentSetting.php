<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentSetting extends Model
{
    protected $fillable = ['is_active', 'bank_name', 'paybill_number', 'account_number', 'account_name', 'instructions'];
    protected $casts = ['is_active' => 'boolean'];
}
