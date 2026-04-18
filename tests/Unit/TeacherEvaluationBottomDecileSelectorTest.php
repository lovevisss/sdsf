<?php

namespace Tests\Unit;

use App\Actions\Ethics\TeacherEvaluationBottomDecileSelector;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class TeacherEvaluationBottomDecileSelectorTest extends TestCase
{
    public function test_it_selects_bottom_ten_percent_by_average_score(): void
    {
        $selector = new TeacherEvaluationBottomDecileSelector();
        $rows = collect(range(1, 20))->map(function (int $index): array {
            return [
                'teacher_no' => 'T'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'teacher_name' => 'Teacher '.$index,
                'average_score' => (float) $index,
                'evaluated_at' => '2026-01-01 00:00:00',
            ];
        });

        /** @var Collection<int, array{teacher_no: string, teacher_name: string, average_score: float, evaluated_at: string}> $selected */
        $selected = $selector->select($rows);

        $this->assertCount(2, $selected);
        $this->assertSame('T001', $selected[0]['teacher_no']);
        $this->assertSame('T002', $selected[1]['teacher_no']);
    }

    public function test_it_keeps_at_least_one_record_when_dataset_is_small(): void
    {
        $selector = new TeacherEvaluationBottomDecileSelector();

        $selected = $selector->select(collect([
            [
                'teacher_no' => 'T101',
                'teacher_name' => 'Teacher 101',
                'average_score' => 89.0,
                'evaluated_at' => '2026-01-01 00:00:00',
            ],
            [
                'teacher_no' => 'T102',
                'teacher_name' => 'Teacher 102',
                'average_score' => 90.0,
                'evaluated_at' => '2026-01-01 00:00:00',
            ],
            [
                'teacher_no' => 'T103',
                'teacher_name' => 'Teacher 103',
                'average_score' => 91.0,
                'evaluated_at' => '2026-01-01 00:00:00',
            ],
        ]));

        $this->assertCount(1, $selected);
        $this->assertSame('T101', $selected[0]['teacher_no']);
    }
}
