<?php

namespace Modules\MedicalSupplier\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierCategory extends Model
{
    use HasFactory;

    protected $table = 'medical_supplier_categories';
    protected $fillable = ['parent_id', 'name', 'slug', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function suppliers(): BelongsToMany { return $this->belongsToMany(MedicalSupplier::class, 'medical_supplier_category_supplier'); }
}
