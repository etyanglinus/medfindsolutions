<?php

namespace Modules\Habit\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Habit extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['status'];

    public function getNameAttribute(): ?string {
        return $this->translation->name;
    }

    public function translation(): ?HasOne {
        return $this->hasOne(HabitTranslation::class)->where('lang_code', getSessionLanguage());
    }

    public function getTranslation($code): ?HabitTranslation {
        return $this->hasOne(HabitTranslation::class)->where('lang_code', $code)->first();
    }

    public function translations(): ?HasMany {
        return $this->hasMany(HabitTranslation::class, 'habit_id');
    }

    public function scopeActive($query) {
        return $query->where('status', 1);
    }
}
