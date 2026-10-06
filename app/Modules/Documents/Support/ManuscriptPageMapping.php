<?php

namespace App\Modules\Documents\Support;

class ManuscriptPageMapping
{
    public static function label(?array $mapping, int $previewPage): string
    {
        if ($mapping === null) {
            return (string) $previewPage;
        }

        $start = (int) $mapping['body_start'];

        return $previewPage >= $start
            ? (string) ($previewPage - $start + 1)
            : (string) ($mapping['preliminary_labels'][$previewPage - 1] ?? 'Unnumbered');
    }
}
