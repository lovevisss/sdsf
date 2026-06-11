<?php

namespace App\Services\Ethics;

class NullAttendanceAbnormalitySource implements AttendanceAbnormalitySource
{
    public function forYear(int $year): array
    {
        return [];
    }
}
