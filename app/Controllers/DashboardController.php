<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Domain\Service\MonthlySummaryService;
use App\Domain\Service\AlertGenerator;



class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ExpenseRepositoryInterface $expenseRepository,
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly AlertGenerator $alertGenerator,
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        $queryParams = $request->getQueryParams();
        $year = (int)($queryParams['year'] ?? date('Y'));
        $month = (int)($queryParams['month'] ?? date('n'));
        // TODO: load the currently logged-in user
        $userId = $_SESSION['user_id'];
        $user = $this->userRepository->find($userId);
        // TODO: get the list of available years for the year-month selector
        $availableYears = $this->expenseRepository->listExpenditureYears($user);
        if (empty($availableYears)) {
            $availableYears = [(int)date('Y')];
        }
        // TODO: call service to generate the overspending alerts for current month
        $alerts = [];
        if($year == date('Y') && $month == date('n')){
            $alerts = $this->alertGenerator->generate($user, $year, $month);
        }
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month
        $totalForMonth = $this->monthlySummaryService->computeTotalExpenditure($user, $year, $month);
        $totalsForCategories = $this->monthlySummaryService->computePerCategoryTotals($user, $year, $month);
        $averagesForCategories = $this->monthlySummaryService->computePerCategoryAverages($user, $year, $month);
        

        return $this->render($response, 'dashboard.twig', [
            'alerts' => $alerts,
            'totalForMonth' => $totalForMonth,
            'totalsForCategories' => $totalsForCategories,
            'averagesForCategories' => $averagesForCategories,
            'availableYears' => $availableYears,
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);
    }
}
