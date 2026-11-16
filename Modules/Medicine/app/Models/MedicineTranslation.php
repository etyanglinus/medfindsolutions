<?php

namespace Modules\Medicine\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineTranslation extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['medicine_id', 'lang_code', 'name'];

    public function medicine(): ?BelongsTo {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
