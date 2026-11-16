<?php

namespace Modules\Medicine\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineTypeTranslation extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['medicine_type_id','lang_code','type'];

    public function medicine_type(): ?BelongsTo {
        return $this->belongsTo(MedicineType::class, 'medicine_type_id');
    }
}
