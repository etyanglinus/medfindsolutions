<?php

namespace Modules\MedicalSupplier\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierProduct extends Model
{
    use HasFactory;

    protected $table = 'medical_supplier_products';
    protected $fillable = ['medical_supplier_id', 'medical_supplier_category_id', 'name', 'slug', 'brand', 'model', 'description', 'specifications', 'image', 'additional_images', 'availability', 'catalogue_path', 'catalogue_is_public', 'is_published', 'approval_status', 'admin_note', 'is_featured'];
    protected $casts = ['additional_images' => 'array', 'is_published' => 'boolean', 'catalogue_is_public' => 'boolean', 'is_featured' => 'boolean'];

    public function supplier(): BelongsTo { return $this->belongsTo(MedicalSupplier::class, 'medical_supplier_id'); }
    public function category(): BelongsTo { return $this->belongsTo(SupplierCategory::class, 'medical_supplier_category_id'); }
    public function quotationRequests(): HasMany { return $this->hasMany(QuotationRequest::class, 'medical_supplier_product_id'); }
    public function analyticsEvents(): HasMany { return $this->hasMany(SupplierAnalyticsEvent::class, 'medical_supplier_product_id'); }
}
