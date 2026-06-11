<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Ethics\EthicsScoreService;
use Illuminate\Http\Request;

trait StoresEthicsViolation
{
    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function prepareViolationPayload(array $validated, Request $request): array
    {
        $severityLevel = isset($validated['severity_level'])
            ? strtoupper(trim((string) $validated['severity_level']))
            : null;
        $fallbackDeduction = isset($validated['deduction_points']) ? (float) $validated['deduction_points'] : null;
        $deductionPoints = app(EthicsScoreService::class)->deductionForSeverity($severityLevel, $fallbackDeduction);

        return [
            ...$validated,
            'severity_level' => $severityLevel ?: null,
            'deduction_points' => $deductionPoints,
            'data_source' => $validated['data_source'] ?? 'manual',
            'handler_department' => $validated['handler_department'] ?? $request->user()?->department?->name,
            'handler_user_id' => $request->user()?->id,
            'deduction_basis' => $validated['deduction_basis'] ?? null,
            'evidence_attachments' => $validated['evidence_attachments'] ?? [],
            'verification_status' => $validated['verification_status'] ?? 'verified',
            'verified_by' => ($validated['verification_status'] ?? 'verified') === 'verified' ? $request->user()?->id : null,
            'verified_at' => ($validated['verification_status'] ?? 'verified') === 'verified' ? now() : null,
        ];
    }
}
