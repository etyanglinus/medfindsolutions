<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model {
    protected $fillable = [
        'visited_on',
        'session_hash',
        'visitor_type',
    ];

    protected $casts = [
        'visited_on' => 'date',
    ];
}
