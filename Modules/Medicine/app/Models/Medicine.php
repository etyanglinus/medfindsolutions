<?php

namespace Modules\Medicine\app\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Medicine extends Model {
    use HasFactory;

    protected $fillable = ['status'];

    public function getNameAttribute(): ?string {
        return $this->translation->name;
    }

    public function translation(): ?HasOne {
        return $this->hasOne(MedicineTranslation::class)->where('lang_code', getSessionLanguage());
    }

    public function getTranslation($code): ?MedicineTranslation {
        return $this->hasOne(MedicineTranslation::class)->where('lang_code', $code)->first();
    }

    public function translations(): ?HasMany {
        return $this->hasMany(MedicineTranslation::class, 'medicine_id');
    }

    public function scopeActive($query) {
        return $query->where('status', 1);
    }
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date) {
        return $date->format('Y-m-d H:i:s');
    }
}
