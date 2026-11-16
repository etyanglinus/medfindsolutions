<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderDocumentReview extends Model
{
    protected $fillable = ['provider_document_id', 'admin_id', 'previous_status', 'status', 'note'];
}
