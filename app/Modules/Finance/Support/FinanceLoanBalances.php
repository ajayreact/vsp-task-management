<?php

namespace App\Modules\Finance\Support;

use App\Modules\Finance\Enums\FinanceLoanStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class FinanceLoanBalances
{
    /**
     * @return array{amount_paid: float, remaining_amount: float, status: FinanceLoanStatus}
     */
    public static function resolve(
        float $loanAmount,
        float $amountPaid,
        FinanceLoanStatus $requestedStatus,
        CarbonInterface|string|null $dueDate = null,
    ): array {
        $loanAmount = max(0, round($loanAmount, 2));
        $amountPaid = max(0, min($loanAmount, round($amountPaid, 2)));
        $remaining = max(0, round($loanAmount - $amountPaid, 2));

        if ($requestedStatus === FinanceLoanStatus::Cancelled) {
            return [
                'amount_paid' => $amountPaid,
                'remaining_amount' => $remaining,
                'status' => FinanceLoanStatus::Cancelled,
            ];
        }

        return [
            'amount_paid' => $amountPaid,
            'remaining_amount' => $remaining,
            'status' => self::deriveStatus($amountPaid, $remaining, $dueDate),
        ];
    }

    public static function deriveStatus(
        float $amountPaid,
        float $remaining,
        CarbonInterface|string|null $dueDate = null,
    ): FinanceLoanStatus {
        if ($remaining <= 0) {
            return FinanceLoanStatus::Paid;
        }

        $due = $dueDate === null || $dueDate === ''
            ? null
            : Carbon::parse($dueDate)->startOfDay();

        if ($due !== null && $due->lt(now()->startOfDay())) {
            return FinanceLoanStatus::Overdue;
        }

        if ($amountPaid > 0) {
            return FinanceLoanStatus::PartiallyPaid;
        }

        return FinanceLoanStatus::Active;
    }
}
