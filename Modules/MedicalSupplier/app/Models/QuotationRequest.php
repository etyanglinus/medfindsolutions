<?php

namespace Modules\MedicalSupplier\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationRequest extends Model
{
    use HasFactory;

    protected $table = 'medical_supplier_quotation_requests';
    protected $fillable = ['medical_supplier_id', 'medical_supplier_product_id', 'name', 'organisation', 'phone', 'email', 'quantity', 'message', 'status'];

    public function supplier(): BelongsTo { return $this->belongsTo(MedicalSupplier::class, 'medical_supplier_id'); }
    public function product(): BelongsTo { return $this->belongsTo(SupplierProduct::class, 'medical_supplier_product_id'); }
    public function statusHistory(): HasMany { return $this->hasMany(QuotationStatusHistory::class, 'quotation_request_id')->orderBy('created_at'); }
    public function getRouteKeyName(): string { return 'id'; }
}
