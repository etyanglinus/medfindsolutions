<?php

namespace Modules\MedicalSupplier\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAnalyticsEvent extends Model
{
    protected $table = 'medical_supplier_analytics_events';

    protected $fillable = ['medical_supplier_id', 'medical_supplier_product_id', 'event_type', 'visitor_hash'];

    public function supplier(): BelongsTo { return $this->belongsTo(MedicalSupplier::class, 'medical_supplier_id'); }
    public function product(): BelongsTo { return $this->belongsTo(SupplierProduct::class, 'medical_supplier_product_id'); }
}
