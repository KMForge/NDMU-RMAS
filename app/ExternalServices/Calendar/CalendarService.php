<?php

namespace App\ExternalServices\Calendar;

interface CalendarService
{
    /** @param array<string, mixed> $event */
    public function publish(array $event): string;
}
