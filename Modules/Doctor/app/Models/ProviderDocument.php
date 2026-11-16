<?php

namespace Modules\Doctor\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderDocument extends Model {
    protected $fillable = ['provider_type', 'provider_id', 'licensing_body', 'verification_body', 'registration_number', 'accreditation_expires_at', 'file_path', 'status', 'admin_note'];

    protected $casts = ['accreditation_expires_at' => 'date'];

    public function reviews(): HasMany { return $this->hasMany(\App\Models\ProviderDocumentReview::class)->latest(); }
}
