<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Score;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;

class MonitorTeacherScores extends Command
{
    protected $signature = 'scores:monitor';
    protected $description = 'Monitor scores and trigger alerts for threshold violations';

    public function handle()
    {
        $this->info('Starting score monitoring...');

        $scores = Score::all();

        foreach ($scores as $score) {
            if ($score->current_score < 60 && $score->current_score >= 40) {
                $this->createAlert($score, 'Yellow', 'Score fell below 60.');
            } elseif ($score->current_score < 40 && $score->current_score >= 20) {
                $this->createAlert($score, 'Orange', 'Score fell below 40.');
            } elseif ($score->current_score < 20) {
                $this->createAlert($score, 'Red', 'Score critically low.');
            }
        }

        $this->info('Score monitoring completed.');
    }

    private function createAlert($score, $level, $message)
    {
        Alert::create([
            'teacher_id' => $score->teacher_id,
            'alert_level' => $level,
            'message' => $message,
        ]);

        Log::info("Alert created: {$level} for teacher ID {$score->teacher_id}");
    }
}