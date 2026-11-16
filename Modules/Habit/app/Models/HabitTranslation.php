<?php

namespace Modules\Habit\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitTranslation extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['habit_id', 'lang_code', 'name'];

    public function habit(): ?BelongsTo {
        return $this->belongsTo(Habit::class, 'habit_id');
    }
}
