<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;


class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
        private readonly \PDO $pdo,
        private readonly ?LoggerInterface $logger = null, 
        private readonly CategoryConfigService $categoryConfig,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        #obtinem nr primului element de pe pagina pageNumber
        $offset = ($pageNumber - 1) * $pageSize;

        $criteria = [
            'user_id' => $user->id,
            'year' => $year,
            'month' => $month
        ];
        $expenses = $this->expenses->findBy($criteria, $offset, $pageSize);
        $total = $this->expenses->countBy($criteria);

        return [
            'items' => $expenses,
            'total' => $total,
            'page' => $pageNumber,
            'pageSize' => $pageSize,
            'totalPages' => (int)ceil($total / $pageSize)
        ];
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): Expense {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }

        if (empty($description)) {
            throw new \InvalidArgumentException('Description cannot be empty');
        }

        if ($date > new DateTimeImmutable()) {
            throw new \InvalidArgumentException('Date can not be in the future');
        }

        $expense = new Expense(
            null,
            $user->id,
            $date,
            $category,
            (int)round($amount * 100),
            $description
        );

        $this->expenses->save($expense);

        return $expense;
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        #validari
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        
        if (empty($description)) {
            throw new \InvalidArgumentException('Description cannot be empty');
        }
        
        if ($date > new DateTimeImmutable()) {
            throw new \InvalidArgumentException('Date cannot be in the future');
        }
        #dam update la valori
        $expense->amountCents = (int)round($amount * 100);
        $expense->description = $description;
        $expense->date = $date;
        $expense->category = $category;
        
        $this->expenses->save($expense);

    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
    $stream = $csvFile->getStream();
    $content = $stream->getContents();
    
    $lines = array_filter(explode("\n", $content));
    $importedCount = 0;
    $skippedRows = [];
    
    $validCategories = $this->categoryConfig->getValidCategoryKeys();
    
    $this->pdo->beginTransaction();
    
    try {
        foreach ($lines as $lineNumber => $line) {
            $data = str_getcsv($line);
            
            if (count($data) < 4) {
                $skippedRows[] = "Line $lineNumber: insufficient columns";
                continue;
            }
            
            [$dateStr, $amount, $description, $category] = $data;
            
            $category = strtolower(trim($category));
            if (!in_array($category, $validCategories)) {
                $skippedRows[] = "Line $lineNumber: unknown category '$category'";
                continue;
            }
            
            try {
                $date = new DateTimeImmutable($dateStr);
                $amount = (float)$amount;
                
                if ($this->isDuplicate($user, $date, $description, $amount, $category)) {
                    $skippedRows[] = "Line $lineNumber: duplicate entry";
                    continue;
                }
                
                $this->create($user, $amount, $description, $date, $category);
                $importedCount++;
                
            } catch (\Exception $e) {
                $skippedRows[] = "Line $lineNumber: " . $e->getMessage();
            }
        }
        
        $this->pdo->commit();
        
        if ($this->logger && !empty($skippedRows)) {
            $this->logger->warning('CSV import skipped rows: ' . implode('; ', $skippedRows));
        }
        
        return $importedCount;
        
    } catch (\Exception $e) {
        $this->pdo->rollBack();
        throw $e;
    }
    }

    private function isDuplicate(User $user, DateTimeImmutable $date, string $description, float $amount, string $category): bool
    {
        
        $existingExpenses = $this->expenses->findBy([
            'user_id' => $user->id,
            'year' => (int)$date->format('Y'),
            'month' => (int)$date->format('n')
        ], 0, 1000);
        
        foreach ($existingExpenses as $expense) {
            if ($expense->date->format('Y-m-d H:i:s') === $date->format('Y-m-d H:i:s') &&
                $expense->description === $description &&
                $expense->amountCents === (int)round($amount * 100) &&
                $expense->category === $category) {
                return true;
            }
        }
        
        return false;
    }

    public function getAvailableYears(User $user): array
    {
        $years = $this->expenses->listExpenditureYears($user);
        $currentYear = (int)date('Y');
        if (!in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }
        rsort($years);

        return $years;
    }

    public function findById(int $id): ?Expense
    {
        return $this->expenses->find($id);
    }

    public function delete(int $id): void
    {
        $this->expenses->delete($id);
    }
}
