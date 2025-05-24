<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
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
            (int)round($amount * 100),  //adaugam valoarea in centi
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
        // TODO: implement this to update expense entity, perform validation, and persist
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
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
}
