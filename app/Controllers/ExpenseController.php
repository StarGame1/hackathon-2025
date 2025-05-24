<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\ExpenseService;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly UserRepositoryInterface $userRepository
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->find($userId);


        // - use the request query parameters to determine the page number and page size
        $queryParams = $request->getQueryParams();
        $page = (int)($queryParams['page'] ?? 1);
        $pageSize = (int)($queryParams['pageSize'] ?? self::PAGE_SIZE);

        $year = (int)($queryParams['year'] ?? date('Y'));
        $month = (int)($queryParams['month'] ?? date('n'));

        $result = $this->expenseService->list($user, $year, $month, $page, $pageSize);
        $availableYears = $this->expenseService->getAvailableYears($user);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => $result['totalPages'],
            'currentYear' => $year,
            'currentMonth' => $month,
            'availableYears' => $availableYears,
        ]);
    }

    private function getCategories(): array
    {
        // TODO: trebe mutate in .env
        return [
            'groceries' => 'Groceries',
            'utilities' => 'Utilities',
            'transport' => 'Transport',
            'entertainment' => 'Entertainment',
            'housing' => 'Housing',
            'health' => 'Healthcare',
            'other' => 'Other'
        ];
    }

    public function create(Request $request, Response $response): Response
    {
        $categories = $this->getCategories();


        return $this->render($response, 'expenses/create.twig', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request, Response $response): Response
    {

        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->find($userId);
        $data = $request->getParsedBody();


        try {
            $this->expenseService->create(
                $user,
                (float)($data['amount'] ?? 0),
                $data['description'] ?? '',
                new DateTimeImmutable($data['date'] ?? 'now'),
                $data['category'] ?? ''
            );
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\InvalidArgumentException $error) {
            return $this->render($response, 'expenses/create.twig', [
                'categories' => $this->getCategories(),
                'errors' => ['general' => $error->getMessage()],
                'formData' => $data
            ]);
        }
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        $expenseId = (int)$routeParams['id'];
        $userId = $_SESSION['user_id'];

        #cautam expense
        $expense = $this->expenseService->findById($expenseId);
        #verificam ca apartine user ului
        if (!$expense || $expense->userId !== $userId) {
            return $response->withStatus(403);
        }

        return $this->render($response, 'expenses/edit.twig', [
            'expense' => $expense,
            'categories' => $this->getCategories()
        ]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        $expenseId = (int)$routeParams['id'];
        $userId = $_SESSION['user_id'];
        $formData = $request->getParsedBody();
        $expense = $this->expenseService->findById($expenseId);
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        if (!$expense || $expense->userId !== $userId) {
            return $response->withStatus(403);
        }
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success
        try {
            $this->expenseService->update(
                $expense,
                (float)($formData['amount'] ?? 0),
                $formData['description'] ?? '',
                new DateTimeImmutable($formData['date'] ?? 'now'),
                $formData['category'] ?? ''
            );
            
            return $response->withHeader('Location', '/expenses')->withStatus(302);
        } catch (\InvalidArgumentException $e) {
            return $this->render($response, 'expenses/edit.twig', [
                'expense' => $expense,
                'categories' => $this->getCategories(),
                'errors' => ['general' => $e->getMessage()],
                'formData' => $formData
            ]);
        }

        return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        $expenseId = (int)$routeParams['id'];
        $userId = $_SESSION['user_id'];
    
        $expense = $this->expenseService->findById($expenseId);
    
        if (!$expense || $expense->userId !== $userId) {
            return $response->withStatus(403);
        }
    
        $this->expenseService->delete($expenseId);
    
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }
}
