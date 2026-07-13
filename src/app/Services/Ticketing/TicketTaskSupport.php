<?php

namespace App\Services\Ticketing;

use Carbon\CarbonInterface;

class TicketTaskSupport
{
    public static function number(string $prefix, CarbonInterface $date, int $sequence): string
    {
        return sprintf('%s-%s-%03d', $prefix, $date->format('Ymd'), $sequence);
    }

    public static function isOverdue(?CarbonInterface $dueAt, bool $final, ?CarbonInterface $now = null): bool
    {
        return ! $final && $dueAt?->isBefore($now ?? now()) === true;
    }
}
