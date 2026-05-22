<?php

namespace App\Support;

class PgVectorHelper
{
    public static function arrayToVectorLiteral(array $values): string
    {
        $normalized = array_map(static function ($value): float {
            return round((float) $value, 8);
        }, $values);

        return '['.implode(',', $normalized).']';
    }

    public static function vectorStringToArray(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $trimmed = trim($value, '[]');

        if ($trimmed === '') {
            return [];
        }

        return array_map(static fn (string $item): float => (float) trim($item), explode(',', $trimmed));
    }
}
