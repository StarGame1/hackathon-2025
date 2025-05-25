<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entity\User;
use App\Domain\Service\AlertGenerator;
use App\Domain\Service\CategoryConfigService;
use App\Domain\Service\MonthlySummaryService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AlertGeneratorTest extends TestCase
{
    private AlertGenerator $alertGenerator;
    private MockObject $summaryService;
    private MockObject $categoryConfig;
    private User $user;
    
    protected function setUp(): void
    {
        $this->summaryService = $this->createMock(MonthlySummaryService::class);
        $this->categoryConfig = $this->createMock(CategoryConfigService::class);
        $this->alertGenerator = new AlertGenerator($this->summaryService, $this->categoryConfig);
        $this->user = new User(1, 'test', 'hash', new \DateTimeImmutable());
    }
    
    public function testGenerateWithinBudget(): void
    {
        $this->summaryService->expects($this->once())
            ->method('computePerCategoryTotals')
            ->willReturn([
                'groceries' => ['value' => 250.00, 'percentage' => 50],
                'utilities' => ['value' => 150.00, 'percentage' => 30],
            ]);
            
        $this->categoryConfig->expects($this->once())
            ->method('getBudgets')
            ->willReturn([
                'groceries' => 300.00,
                'utilities' => 200.00,
            ]);
        
        $alerts = $this->alertGenerator->generate($this->user, 2025, 5);
        
        $this->assertCount(1, $alerts);
        $this->assertSame('success', $alerts[0]['type']);
        $this->assertStringContainsString('within budget', $alerts[0]['message']);
    }
    
    public function testGenerateOverBudget(): void
    {
        $this->summaryService->expects($this->once())
            ->method('computePerCategoryTotals')
            ->willReturn([
                'groceries' => ['value' => 350.00, 'percentage' => 70],
            ]);
            
        $this->categoryConfig->expects($this->once())
            ->method('getBudgets')
            ->willReturn([
                'groceries' => 300.00,
            ]);
        
        $alerts = $this->alertGenerator->generate($this->user, 2025, 5);
        
        $this->assertCount(1, $alerts);
        $this->assertSame('warning', $alerts[0]['type']);
        $this->assertStringContainsString('groceries budget exceeded by 50.00 €', $alerts[0]['message']);
    }
}