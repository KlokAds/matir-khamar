<?php

namespace App\Support;

/** Taka typed in forms <-> poisha stored in the books. */
final class Money
{
    public static function toPoisha(float|int|string|null $taka): int
    {
        return (int) round(((float) $taka) * 100);
    }

    /**
     * Validation rule for a taka amount typed into a form.
     *
     * @return list<string>
     */
    public static function rule(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'numeric', 'min:0.01', 'max:999999999'];
    }
}
