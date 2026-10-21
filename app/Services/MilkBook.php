<?php

namespace App\Services;

use App\Models\CostCenter;
use App\Models\MilkRecord;

/** Milk yield per cow and milking session. Saving the same session again replaces it. */
class MilkBook
{
    /**
     * @param  list<array{animal_id?:int|null, liters?:float|int|string|null}>  $rows
     * @return int rows saved
     */
    public function record(string $date, string $session, array $rows, int $userId): int
    {
        $gavi = CostCenter::query()->where('code', 'GAVI')->value('id');
        $saved = 0;

        foreach ($rows as $row) {
            if ((float) ($row['liters'] ?? 0) <= 0) {
                continue;
            }

            MilkRecord::query()->updateOrCreate(
                ['date' => $date, 'session' => $session, 'animal_id' => $row['animal_id'] ?? null],
                ['liters' => $row['liters'], 'cost_center_id' => $gavi, 'recorded_by' => $userId],
            );
            $saved++;
        }

        return $saved;
    }
}
