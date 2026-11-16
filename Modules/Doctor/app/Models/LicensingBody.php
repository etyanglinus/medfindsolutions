<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicensingBody extends Model {
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
    ];

    public static function activeDoctorBodies() {
        return self::where('type', 'doctor')->where('status', true)->orderBy('name')->get();
    }

    public static function activeFacilityBodies() {
        return self::where('type', 'facility')->where('status', true)->orderBy('name')->get();
    }
}
