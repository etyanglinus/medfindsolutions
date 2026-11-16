<?php

namespace Modules\MedicalSupplier\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationStatusHistory extends Model
{
    protected $table = 'medical_supplier_quotation_status_history';
    protected $fillable = ['quotation_request_id', 'status', 'actor_type', 'actor_id'];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(QuotationRequest::class, 'quotation_request_id');
    }
}
