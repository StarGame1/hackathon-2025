<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\MonthlySummaryService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MonthlySummaryServiceTest extends TestCase
{
    private MonthlySummaryService $service;
    private MockObject $expenseRepository;
    private User $user;
    
    protected function setUp(): void
    {
        $this->expenseRepository = $this->createMock(ExpenseRepositoryInterface::class);
        $this->service = new MonthlySummaryService($this->expenseRepository);
        $this->user = new User(1, 'testuser', 'hash', new \DateTimeImmutable());
    }
    
    public function testComputeTotalExpenditure(): void
    {
        $this->expenseRepository->expects($this->once())
            ->method('sumAmounts')
            ->with([
                'user_id' => 1,
                'year' => 2025,
                'month' => 5
            ])
            ->willReturn(150.50);
        
        $total = $this->service->computeTotalExpenditure($this->user, 2025, 5);
        
        $this->assertSame(150.50, $total);
    }
    
    public function testComputePerCategoryTotals(): void
    {
        $this->expenseRepository->expects($this->once())
            ->method('sumAmountsByCategory')
            ->willReturn([
                'groceries' => 100.00,
                'transport' => 50.00,
            ]);
        
        $result = $this->service->computePerCategoryTotals($this->user, 2025, 5);
        
        $this->assertArrayHasKey('groceries', $result);
        $this->assertSame(100.00, $result['groceries']['value']);
        $this->assertEqualsWithDelta(66.67, $result['groceries']['percentage'], 0.01);
        
        $this->assertArrayHasKey('transport', $result);
        $this->assertSame(50.00, $result['transport']['value']);
        $this->assertEqualsWithDelta(33.33, $result['transport']['percentage'], 0.01);
    }
}