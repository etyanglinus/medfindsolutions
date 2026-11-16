<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProvider extends Model {
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'sort_order',
    ];

    public static function active() {
        return self::where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
