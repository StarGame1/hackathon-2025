<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if ($password !== $passwordConfirm) {
            return $this->render($response, 'auth/register.twig', [
                'username' => $username,
                'errors' => ['password_confirm' => 'Passwords does not match']
            ]);
        }

        try {
            $user = $this->authService->register(
                $username,
                $password
            );
            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (\InvalidArgumentException $error) {
            $errorMessage = $error->getMessage();
            $errors = [];

            if (stripos($errorMessage, 'username') !== false) {
                $errors['username'] = $errorMessage;
            } elseif (stripos($errorMessage, 'password') !== false) {
                $errors['password'] = $errorMessage;
            }
            return $this->render($response, 'auth/register.twig', [
                'username' => $data['username'],
                'errors' => $errors
            ]);
        }
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig', ['error' => 'Invalid user or password']);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $is_success = $this->authService->attempt(
            $data['username'] ?? '',
            $data['password'] ?? ''
        );

        if ($is_success) {
            return $response->withHeader('Location', '/')->withStatus(302);
        }

        return $this->render($response, 'auth/login.twig', ['error' => 'Invalid user or password']);
    }

    public function logout(Request $request, Response $response): Response
    {
        session_start();
        $_SESSION = [];
        session_destroy();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
