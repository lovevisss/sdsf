<?php

namespace App\Actions\Ethics;

use Illuminate\Support\Collection;

class TeacherEvaluationBottomDecileSelector
{
    /**
     * @param  Collection<int, array{teacher_no: string, teacher_name: string, average_score: float, evaluated_at: string, academic_year?: string}>  $rows
     * @return Collection<int, array{teacher_no: string, teacher_name: string, average_score: float, evaluated_at: string, academic_year?: string}>
     */
    public function select(Collection $rows): Collection
    {
        $total = $rows->count();

        if ($total === 0) {
            return collect();
        }

        $bottomCount = max(1, (int) ceil($total * 0.10));

        return $rows
            ->sort(function (array $left, array $right): int {
                if ($left['average_score'] === $right['average_score']) {
                    return strcmp($left['teacher_no'], $right['teacher_no']);
                }

                return $left['average_score'] <=> $right['average_score'];
            })
            ->take($bottomCount)
            ->values();
    }
}
