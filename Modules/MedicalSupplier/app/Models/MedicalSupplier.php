<?php

namespace Modules\MedicalSupplier\app\Models;

use App\Models\ProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalSupplier extends Authenticatable
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    use HasFactory;

    protected $fillable = ['provider_profile_id', 'name', 'slug', 'official_email', 'phone', 'contact_person', 'whatsapp', 'website', 'logo', 'cover_image', 'description', 'year_established', 'address', 'counties_served', 'supplier_types', 'brands', 'services', 'social_links', 'registration_number', 'verification_body', 'gallery_images', 'is_featured', 'promotional_banner', 'promotional_banner_ends_at', 'promotional_banner_approved', 'password', 'registration_token', 'email_verified_at', 'email_verified_token'];

    protected $hidden = ['password', 'remember_token', 'registration_token', 'email_verified_token'];

    protected $casts = ['counties_served' => 'array', 'supplier_types' => 'array', 'brands' => 'array', 'services' => 'array', 'social_links' => 'array', 'gallery_images' => 'array', 'is_featured' => 'boolean', 'promotional_banner_ends_at' => 'date', 'promotional_banner_approved' => 'boolean', 'password' => 'hashed', 'email_verified_at' => 'datetime'];

    public function providerProfile(): BelongsTo { return $this->belongsTo(ProviderProfile::class); }
    public function categories(): BelongsToMany { return $this->belongsToMany(SupplierCategory::class, 'medical_supplier_category_supplier', 'medical_supplier_id', 'medical_supplier_category_id'); }
    public function products(): HasMany { return $this->hasMany(SupplierProduct::class); }
    public function quotationRequests(): HasMany { return $this->hasMany(QuotationRequest::class); }
    public function analyticsEvents(): HasMany { return $this->hasMany(SupplierAnalyticsEvent::class); }
    public function documents(): HasMany { return $this->hasMany(\Modules\Doctor\app\Models\ProviderDocument::class, 'provider_id')->where('provider_type', 'supplier'); }

}
