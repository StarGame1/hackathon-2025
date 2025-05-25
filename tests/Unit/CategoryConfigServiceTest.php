<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Service\CategoryConfigService;
use PHPUnit\Framework\TestCase;

class CategoryConfigServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['EXPENSE_CATEGORIES'] = '{"groceries":"Groceries","utilities":"Utilities"}';
        $_ENV['CATEGORY_BUDGETS'] = '{"groceries":300,"utilities":200}';
    }
    
    public function testGetCategories(): void
    {
        $service = new CategoryConfigService();
        $categories = $service->getCategories();
        
        $this->assertCount(2, $categories);
        $this->assertSame('Groceries', $categories['groceries']);
        $this->assertSame('Utilities', $categories['utilities']);
    }
    
    public function testGetBudgets(): void
    {
        $service = new CategoryConfigService();
        $budgets = $service->getBudgets();
        
        $this->assertEquals(300, $budgets['groceries']);
        $this->assertEquals(200, $budgets['utilities']);
    }
    
    public function testGetBudgetForCategory(): void
    {
        $service = new CategoryConfigService();
        
        $this->assertEquals(300, $service->getBudgetForCategory('groceries'));
        $this->assertEquals(0, $service->getBudgetForCategory('unknown'));
    }
    
    public function testGetValidCategoryKeys(): void
    {
        $service = new CategoryConfigService();
        $keys = $service->getValidCategoryKeys();
        
        $this->assertSame(['groceries', 'utilities'], $keys);
    }
}