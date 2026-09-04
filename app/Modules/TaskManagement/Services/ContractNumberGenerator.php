<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\TaskManagement\Models\Contract;

class ContractNumberGenerator
{
    public function next(): string
    {
        $prefix = (string) config('contracts.number_prefix', 'VSP-CONTRACT');
        $year = now()->format('Y');
        $pattern = "{$prefix}-{$year}-%";

        $latest = Contract::query()
            ->where('contract_number', 'like', $pattern)
            ->orderByDesc('contract_number')
            ->value('contract_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }
}
