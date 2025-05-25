<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id AND deleted_at IS NULL';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        if ($expense->id === null) {
            #insert cand nu exista
            $query = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) 
                      VALUES (:user_id, :date, :category, :amount_cents, :description)';

            $statement = $this->pdo->prepare($query);
            $statement->execute([
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d H:i:s'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);


            $expense->id = (int)$this->pdo->lastInsertId();
        } else {
            #update cand exista
            $query = 'UPDATE expenses 
                      SET date = :date, 
                          category = :category, 
                          amount_cents = :amount_cents, 
                          description = :description
                      WHERE id = :id AND user_id = :user_id'; #facem check pe user id pentru securizare

            $statement = $this->pdo->prepare($query);
            $statement->execute([
                'id' => $expense->id,
                'user_id' => $expense->userId,
                'date' => $expense->date->format('Y-m-d H:i:s'),
                'category' => $expense->category,
                'amount_cents' => $expense->amountCents,
                'description' => $expense->description,
            ]);
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE expenses SET deleted_at = :deleted_at WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        #adunam dinamic conditiile WHERE
        $params = [];
        $conditions = [];
        $conditions[] = 'deleted_at IS NULL';

        if (isset($criteria['user_id'])) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['year']) && isset($criteria['month'])) {
            $conditions[] = "strftime('%Y', date) = :year";
            $conditions[] = "strftime('%m', date) = :month";
            $params['year'] = sprintf('%04d', $criteria['year']);
            $params['month'] = sprintf('%02d', $criteria['month']);
        }
        
        #realizam clauza cu conditiile adunate
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $query = "SELECT * FROM expenses 
                  $whereClause 
                  ORDER BY date DESC 
                  LIMIT :limit OFFSET :offset";

        $statement = $this->pdo->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $from, PDO::PARAM_INT);

        $statement->execute();

        $expenses = [];
        while ($data = $statement->fetch()) {
            $expenses[] = $this->createExpenseFromData($data);
        }

        return $expenses;

        return [];
    }


    public function countBy(array $criteria): int
    {
        #adunam conditii WHERE dinamic
        $conditions = [];
        $params = [];
        $conditions[] = 'deleted_at IS NULL';

        if (isset($criteria['user_id'])) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }

        if (isset($criteria['year']) && isset($criteria['month'])) {
            $conditions[] = "strftime('%Y', date) = :year";
            $conditions[] = "strftime('%m', date) = :month";
            $params['year'] = sprintf('%04d', $criteria['year']);
            $params['month'] = sprintf('%02d', $criteria['month']);
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $query = "SELECT COUNT(*) as total FROM expenses $whereClause";
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        return (int)$statement->fetch()['total'];
    }

    public function listExpenditureYears(User $user): array
    {
        $query = "SELECT DISTINCT strftime('%Y', date) as year 
                  FROM expenses 
                  WHERE user_id = :user_id 
                  ORDER BY year DESC";

        $statement = $this->pdo->prepare($query);
        $statement->execute(['user_id' => $user->id]);

        $years = [];
        while ($row = $statement->fetch()) {
            $years[] = (int)$row['year'];
        }

        return $years;
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        $conditions = [];
        $params = [];
        $conditions[] = 'deleted_at IS NULL';
        
        if (isset($criteria['user_id'])) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }
        
        if (isset($criteria['year']) && isset($criteria['month'])) {
            $conditions[] = "strftime('%Y', date) = :year";
            $conditions[] = "strftime('%m', date) = :month";
            $params['year'] = sprintf('%04d', $criteria['year']);
            $params['month'] = sprintf('%02d', $criteria['month']);
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $query = "SELECT category, SUM(amount_cents) as total 
                  FROM expenses 
                  $whereClause 
                  GROUP BY category";
                  
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        
        $results = [];
        while ($row = $statement->fetch()) {
            $results[$row['category']] = $row['total'] / 100;
        }
        
        return $results;
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        $conditions = [];
        $params = [];
        $conditions[] = 'deleted_at IS NULL';
        
        if (isset($criteria['user_id'])) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }
        
        if (isset($criteria['year']) && isset($criteria['month'])) {
            $conditions[] = "strftime('%Y', date) = :year";
            $conditions[] = "strftime('%m', date) = :month";
            $params['year'] = sprintf('%04d', $criteria['year']);
            $params['month'] = sprintf('%02d', $criteria['month']);
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $query = "SELECT category, AVG(amount_cents) as average 
                  FROM expenses 
                  $whereClause 
                  GROUP BY category";
                  
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        
        $results = [];
        while ($row = $statement->fetch()) {
            $results[$row['category']] = $row['average'] / 100;
        }
        
        return $results;
    }

    public function sumAmounts(array $criteria): float
    {
        $conditions = [];
        $params = [];
        $conditions[] = 'deleted_at IS NULL';
        
        #verificam daca e user_id potrivit
        if (isset($criteria['user_id'])) {
            $conditions[] = 'user_id = :user_id';
            $params['user_id'] = $criteria['user_id'];
        }
        #criteriu an si luna
        if (isset($criteria['year']) && isset($criteria['month'])) {
            $conditions[] = "strftime('%Y', date) = :year";
            $conditions[] = "strftime('%m', date) = :month";
            $params['year'] = sprintf('%04d', $criteria['year']);
            $params['month'] = sprintf('%02d', $criteria['month']);
        }
        #creem clauza where dinamic
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $query = "SELECT COALESCE(SUM(amount_cents), 0) as total FROM expenses $whereClause";
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        
        $result = $statement->fetch();
        return $result['total'] / 100;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }
}
