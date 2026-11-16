<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileView extends Model
{
    protected $fillable = ['profile_type', 'profile_id', 'viewed_on', 'session_hash'];

    protected $casts = ['viewed_on' => 'date'];
}
