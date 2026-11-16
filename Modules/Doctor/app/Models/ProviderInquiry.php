<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderInquiry extends Model {
    protected $fillable = ['provider_type', 'provider_id', 'patient_name', 'phone', 'email', 'reason', 'preferred_contact_method', 'status'];
}
