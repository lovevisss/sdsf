<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEthicsWarningRequest;
use App\Models\EthicsWarning;

class EthicsWarningController extends Controller
{
    public function store(StoreEthicsWarningRequest $request)
    {
        $this->authorize('create', EthicsWarning::class);

        EthicsWarning::query()->create([
            ...$request->validated(),
            'status' => 'open',
            'detected_at' => now(),
        ]);

        return redirect()->back()->with('success', '预警已创建。');
    }

    public function close(EthicsWarning $warning)
    {
        $this->authorize('close', $warning);

        $warning->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()->back()->with('success', '预警已销号。');
    }
}

