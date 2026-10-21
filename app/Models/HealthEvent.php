<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vaccine, deworming or treatment, for one animal or for a whole category at once,
 * and when the next one falls due.
 *
 * @property int $id
 * @property int|null $animal_id
 * @property int|null $cost_center_id
 * @property string $kind vaccine, deworm, treatment or checkup
 * @property string $name
 * @property CarbonImmutable $given_on
 * @property CarbonImmutable|null $next_due_on
 * @property string|null $dose
 * @property string|null $by_whom
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Animal|null $animal
 * @property-read CostCenter|null $costCenter
 */
#[Fillable(['animal_id', 'cost_center_id', 'kind', 'name', 'given_on', 'next_due_on', 'dose', 'by_whom', 'notes', 'created_by'])]
class HealthEvent extends Model
{
    use Auditable;

    public const KINDS = [
        'vaccine' => 'টিকা',
        'deworm' => 'কৃমিনাশক',
        'treatment' => 'চিকিৎসা',
        'checkup' => 'ডাক্তারি পরীক্ষা',
    ];

    /**
     * Common ones with the usual gap before the next dose, in months. A starting point —
     * the vet's schedule wins.
     *
     * @var list<array{kind:string, name:string, months:int|null}>
     */
    public const PRESETS = [
        ['kind' => 'vaccine', 'name' => 'ক্ষুরা রোগ (FMD)', 'months' => 6],
        ['kind' => 'vaccine', 'name' => 'গলাফোলা (HS)', 'months' => 12],
        ['kind' => 'vaccine', 'name' => 'বাদলা (BQ)', 'months' => 12],
        ['kind' => 'vaccine', 'name' => 'তড়কা (Anthrax)', 'months' => 12],
        ['kind' => 'vaccine', 'name' => 'লাম্পি স্কিন (LSD)', 'months' => 12],
        ['kind' => 'deworm', 'name' => 'কৃমিনাশক', 'months' => 4],
        ['kind' => 'treatment', 'name' => 'চিকিৎসা', 'months' => null],
        ['kind' => 'checkup', 'name' => 'ডাক্তারি পরীক্ষা', 'months' => null],
    ];

    protected function casts(): array
    {
        return [
            'given_on' => 'date',
            'next_due_on' => 'date',
        ];
    }

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    /** @return BelongsTo<CostCenter, $this> */
    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function target(): string
    {
        return $this->animal->tag ?? ($this->costCenter ? "পুরো {$this->costCenter->name}" : 'পুরো খামার');
    }
}
