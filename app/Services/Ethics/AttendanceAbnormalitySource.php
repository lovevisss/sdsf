<?php

namespace App\Services\Ethics;

interface AttendanceAbnormalitySource
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forYear(int $year): array;
}
