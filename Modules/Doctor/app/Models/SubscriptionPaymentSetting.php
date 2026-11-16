<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentSetting extends Model
{
    protected $fillable = ['paybill_number', 'account_number', 'account_name', 'instructions'];
}
