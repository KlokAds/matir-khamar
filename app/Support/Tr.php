<?php

namespace App\Support;

/**
 * English for text the server builds. The Bangla text is the key (lang/en.json); a phrase
 * missing there, or any request in Bangla, gets the Bangla back unchanged.
 */
final class Tr
{
    /**
     * Translate every label of a list such as Animal::STATUSES, keeping its keys.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, string>  $labels
     * @return array<TKey, string>
     */
    public static function all(array $labels): array
    {
        return array_map(fn (string $label): string => self::get($label), $labels);
    }

    /**
     * One phrase, always as a string.
     *
     * @param  array<string, string|int|float>  $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $line = __($key, $replace);

        return is_string($line) ? $line : $key;
    }
}
