<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Ethics\StaffMonthlyAttendanceSyncService;
use Illuminate\Console\Command;

class SyncStaffMonthlyAttendance extends Command
{
    protected $signature = 'ethics:sync-staff-monthly-attendance
                            {statMonth? : Attendance month in YYYY-MM format}
                            {--year= : Sync all months in this year when statMonth is omitted}
                            {--recorder-user-id= : Local user id used as recorder}
                            {--dry-run : Preview only, do not write records}';

    protected $description = 'Sync monthly staff attendance abnormalities into ethics discipline violations.';

    public function __construct(private readonly StaffMonthlyAttendanceSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $recorderUserId = $this->resolveRecorderUserId();

        if ($recorderUserId === null) {
            return self::FAILURE;
        }

        $yearOption = $this->option('year');
        $year = $yearOption !== null && $yearOption !== '' ? (int) $yearOption : null;

        try {
            $result = $this->syncService->sync(
                statMonth: $this->argument('statMonth'),
                year: $year,
                recorderUserId: $recorderUserId,
                dryRun: (bool) $this->option('dry-run'),
            );
        } catch (\Throwable $exception) {
            $this->error('Failed to sync staff monthly attendance: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sync completed. read=%d inserted=%d skipped=%d below_threshold=%d',
            $result['read'],
            $result['inserted'],
            $result['skipped'],
            $result['below_threshold'],
        ));

        return self::SUCCESS;
    }

    private function resolveRecorderUserId(): ?int
    {
        $optionValue = $this->option('recorder-user-id');
        $configuredValue = config('ethics.auto_recorder_user_id');
        $raw = $optionValue !== null && $optionValue !== '' ? $optionValue : $configuredValue;

        if ($raw === null || $raw === '') {
            $this->error('Missing recorder user id. Configure ETHICS_AUTO_RECORDER_USER_ID or use --recorder-user-id.');

            return null;
        }

        $userId = (int) $raw;

        if ($userId < 1 || ! User::query()->whereKey($userId)->exists()) {
            $this->error("Recorder user id {$userId} does not exist.");

            return null;
        }

        return $userId;
    }
}
