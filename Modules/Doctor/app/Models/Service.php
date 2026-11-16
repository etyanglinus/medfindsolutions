<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    use HasFactory;

    protected $fillable = ['name','status'];

    public static function active() {
        return self::query()
            ->whereNotNull('name')
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }
}
