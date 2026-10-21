<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['date', 'session', 'cost_center_id', 'animal_id', 'liters', 'recorded_by'])]
class MilkRecord extends Model
{
    public const SESSIONS = ['morning' => 'সকাল', 'evening' => 'বিকাল'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'liters' => 'float',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
