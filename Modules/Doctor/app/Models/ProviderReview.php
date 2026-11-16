<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderReview extends Model {
    protected $fillable = ['provider_type', 'provider_id', 'reviewer_name', 'reviewer_email', 'rating', 'review', 'status', 'report_reason'];
}
