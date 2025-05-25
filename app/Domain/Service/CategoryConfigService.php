<?php

declare(strict_types=1);

namespace App\Domain\Service;

class CategoryConfigService
{
    private array $categories;
    private array $budgets;
    
    public function __construct()
    {
        #facem decode din string
        $categoriesJson = $_ENV['EXPENSE_CATEGORIES'] ?? '{}';
        $budgetsJson = $_ENV['CATEGORY_BUDGETS'] ?? '{}';
        
        $this->categories = json_decode($categoriesJson, true) ?: [];
        $this->budgets = json_decode($budgetsJson, true) ?: [];
    }
    
    public function getCategories(): array
    {
        return $this->categories;
    }
    
    public function getBudgets(): array
    {
        return $this->budgets;
    }
    
    public function getBudgetForCategory(string $category): float
    {
        return $this->budgets[$category] ?? 0.0;
    }
    
    public function getCategoryLabel(string $key): string
    {
        return $this->categories[$key] ?? ucfirst($key);
    }
    
    public function getValidCategoryKeys(): array
    {
        return array_keys($this->categories);
    }
}