<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use InvalidArgumentException;

class AuthService
{
    private const MIN_USERNAME_LENGTH = 4;
    private const MIN_PASSWORD_LENGTH = 8;
    private const PASSWORD_REGEX = '/[0-9]/';

    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    private function validateUsername(string $username): void
    {
        if (strlen($username) < self::MIN_USERNAME_LENGTH) {
            throw new InvalidArgumentException('Username must be at least ' . self::MIN_USERNAME_LENGTH . ' characters long.');
        }
    }


    private function validatePassword(string $password): void
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException('Password must be at least' . self::MIN_PASSWORD_LENGTH . 'characters long.');
        }
        if (!preg_match(self::PASSWORD_REGEX, $password)) {
            throw new InvalidArgumentException('Password must contain at least a number');
        }
    }


    private function isUniqueUsername(string $username): void
    {
        if ($this->users->findByUsername($username) !== null) {
            throw new InvalidArgumentException('Username already exists');
        }
    }

    private function createUser(string $username, string $password): User
    {
        return new User(
            null,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            new \DateTimeImmutable()
        );
    }


    public function register(string $username, string $password): User
    {


        $this->validateUsername($username);
        $this->validatePassword($password);
        $this->isUniqueUsername($username);
        $user = $this->createUser($username, $password);
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user->passwordHash)) {
            return false;
        }

        #Incepem o sesiune si salvam id si username
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    

        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

        session_regenerate_id(true);

        return true;
    }
}
