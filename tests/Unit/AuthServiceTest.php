<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Service\AuthService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private MockObject $userRepository;
    
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepository);
    }
    
    public function testRegisterWithValidData(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByUsername')
            ->with('testuser')
            ->willReturn(null);
            
        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return $user->username === 'testuser'
                    && password_verify('password123', $user->passwordHash);
            }));
        
        $user = $this->authService->register('testuser', 'password123');
        
        $this->assertSame('testuser', $user->username);
        $this->assertTrue(password_verify('password123', $user->passwordHash));
    }
    
    public function testRegisterWithShortUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username must be at least 4 characters long');
        
        $this->authService->register('abc', 'password123');
    }
    
    public function testRegisterWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');
        
        $this->authService->register('testuser', 'pass123');
    }
    
    public function testRegisterWithPasswordWithoutNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one number');
        
        $this->authService->register('testuser', 'password');
    }
    
    public function testRegisterWithExistingUsername(): void
    {
        $existingUser = new User(1, 'testuser', 'hash', new \DateTimeImmutable());
        
        $this->userRepository->expects($this->once())
            ->method('findByUsername')
            ->with('testuser')
            ->willReturn($existingUser);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username already exists');
        
        $this->authService->register('testuser', 'password123');
    }
}