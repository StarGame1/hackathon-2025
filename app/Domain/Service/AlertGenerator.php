<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;

class AlertGenerator
{
    public function __construct(
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly CategoryConfigService $categoryConfig,
    )
    {
        
    }

    // TODO: refactor the array below and make categories and their budgets configurable in .env
    // Hint: store them as JSON encoded in .env variable, inject them manually in a dedicated service,
    // then inject and use use that service wherever you need category/budgets information.
    private array $categoryBudgets = [
        'groceries' => 300.00,   
        'utilities' => 200.00,
        'transport' => 500.00,
        'entertainment' => 150.00,
        'housing' => 800.00,
        'health' => 100.00,
        'other' => 100.00
    ];

    public function generate(User $user, int $year, int $month): array
    {
        $alerts = [];
        #luam datele despre total pe categorie
        $totalsD = $this->monthlySummaryService->computePerCategoryTotals($user, $year, $month);


        #extragem doar valoarea
        $totals = [];
        foreach($totalsD as $category => $data){
            $totals[$category] = $data['value'];
        }

        $budgets = $this->categoryConfig->getBudgets();
        
        #verificam daca e overspent pt fiecare categorie
        foreach($this->categoryBudgets as $category => $budget){
            $key = strtolower($category) ;
            $spent = $totals[$key] ?? 0;
            if($spent > $budget){
                $overspent = $spent - $budget;
                $alerts[] = [
                    'type' => 'warning',
                    'message' => sprintf('%s budget exceeded by %.2f €', $category, $overspent)
                ];
            }

        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'success',
                'message' => 'Looking good! You are within budget for this month.'
            ];
        }
        
        return $alerts;

    }
}
