<?php

namespace App\Support;

/** Barajado determinístico: el mismo intento ve siempre el mismo orden de opciones. */
class Seeded
{
    public static function shuffle(array $items, string $seed): array
    {
        $items = array_values($items);
        $state = crc32($seed);
        for ($i = count($items) - 1; $i > 0; $i--) {
            $state = ($state * 1103515245 + 12345) & 0x7FFFFFFF;
            $j = $state % ($i + 1);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }

        return $items;
    }
}
