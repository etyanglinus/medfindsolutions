<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicBranch extends Model {
    protected $fillable = ['clinic_id', 'name', 'address', 'phone'];
}
