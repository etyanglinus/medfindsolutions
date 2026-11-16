<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminProviderMessage extends Model
{
    protected $fillable = ['provider_type', 'provider_id', 'admin_id', 'sender_type', 'message', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
}
