<?php

namespace Tests\Unit;

use App\Models\Staff;
use Tests\TestCase;

class StaffTest extends TestCase
{
    public function test_it_builds_archive_data_from_dwmc(): void
    {
        $staff = new Staff();
        $staff->forceFill([
            'gh' => 'T2026001',
            'xm' => '张老师',
            'dwmc' => '计算机学院',
        ]);

        $this->assertSame('T2026001', $staff->staff_no);
        $this->assertSame('张老师', $staff->name);
        $this->assertSame('计算机学院', $staff->unit_name);
        $this->assertSame([
            'staff_no' => 'T2026001',
            'name' => '张老师',
            'unit_name' => '计算机学院',
        ], $staff->toArchiveArray());
    }

    public function test_it_falls_back_to_bmmc_for_unit_name(): void
    {
        $staff = new Staff();
        $staff->forceFill([
            'gh' => 'T2026002',
            'xm' => '李老师',
            'bmmc' => '数学学院',
        ]);

        $this->assertSame('数学学院', $staff->unit_name);
    }
}

