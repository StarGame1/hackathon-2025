<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotalExpenditure(User $user, int $year, int $month): float
    {
        return $this->expenses->sumAmounts([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month
        ]);
    }

    public function computePerCategoryTotals(User $user, int $year, int $month): array
    {
        $totals = $this->expenses->sumAmountsByCategory([
        'user_id' => $user->id,
        'year' => $year,
        'month' => $month]);

        
        $total = array_sum($totals);
        $result = [];
        
        foreach ($totals as $category => $amount) {
            $result[$category] = [
                'value' => $amount,
                'percentage' => $total > 0 ? ($amount / $total) * 100 : 0
            ];
        }
        
        return $result;
    }

    public function computePerCategoryAverages(User $user, int $year, int $month): array
    {
        $averages = $this->expenses->averageAmountsByCategory([
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month
        ]);
        
        $maxAverage = $averages ? max($averages) : 0;
        $result = [];
        
        foreach ($averages as $category => $amount) {
            $result[$category] = [
                'value' => $amount,
                'percentage' => $maxAverage > 0 ? ($amount / $maxAverage) * 100 : 0
            ];
        }
        
        return $result;
    }
}
