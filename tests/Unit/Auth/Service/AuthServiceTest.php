<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Service;

use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\RegisterRequest;
use App\Auth\Enum\UserLocale;
use App\Auth\Service\AuthService;
use App\Common\Exception\ApiException;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(AuthService::class)]
final class AuthServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private RefreshTokenRepository&MockObject $refreshTokenRepository;
    private EntityManagerInterface&MockObject $em;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private JWTTokenManagerInterface&MockObject $jwtManager;
    private AuthService $sut;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);

        $this->sut = new AuthService(
            $this->userRepository,
            $this->refreshTokenRepository,
            $this->em,
            $this->passwordHasher,
            $this->jwtManager,
        );
    }

    // --- register ---

    #[Test]
    public function testRegisterSuccessReturnsUserTokenAndRefreshToken(): void
    {
        $dto = new RegisterRequest(
            email: 'test@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe',
            locale: UserLocale::EN,
        );

        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_password');
        $this->jwtManager->method('create')->willReturn('jwt_token');

        $this->em->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(function (object $entity): void {
                static $call = 0;
                $call++;
                if ($call === 1) {
                    self::assertInstanceOf(User::class, $entity);
                } else {
                    self::assertInstanceOf(RefreshToken::class, $entity);
                }
            });

        $this->em->expects(self::exactly(2))->method('flush');

        $result = $this->sut->register($dto);

        self::assertArrayHasKey('user', $result);
        self::assertArrayHasKey('token', $result);
        self::assertArrayHasKey('refreshToken', $result);
        self::assertSame('jwt_token', $result['token']);
        self::assertSame('test@example.com', $result['user']['email']);
        self::assertSame('John', $result['user']['firstName']);
        self::assertSame('Doe', $result['user']['lastName']);
        self::assertSame('en', $result['user']['locale']);
        self::assertIsString($result['refreshToken']);
        self::assertSame(128, strlen($result['refreshToken']));
    }

    #[Test]
    public function testRegisterWithDuplicateEmailThrowsApiException(): void
    {
        $dto = new RegisterRequest(email: 'taken@example.com', password: 'password123', firstName: 'A', lastName: 'B');

        $existingUser = new User();
        $existingUser->setEmail('taken@example.com');
        $this->userRepository->method('findByEmail')->willReturn($existingUser);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('A user with this email already exists.');

        $this->sut->register($dto);
    }

    #[Test]
    public function testRegisterDuplicateEmailReturns409(): void
    {
        $dto = new RegisterRequest(email: 'taken@example.com', password: 'password123', firstName: 'A', lastName: 'B');
        $this->userRepository->method('findByEmail')->willReturn(new User());

        try {
            $this->sut->register($dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('EMAIL_ALREADY_EXISTS', $e->errorCode);
        }
    }

    #[Test]
    public function testRegisterCatchesUniqueConstraintViolation(): void
    {
        $dto = new RegisterRequest(email: 'race@example.com', password: 'password123', firstName: 'A', lastName: 'B');

        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $this->em->method('flush')->willThrowException(
            $this->createMock(UniqueConstraintViolationException::class),
        );

        try {
            $this->sut->register($dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(409, $e->statusCode);
            self::assertSame('EMAIL_ALREADY_EXISTS', $e->errorCode);
        }
    }

    #[Test]
    public function testRegisterDefaultLocaleIsVietnamese(): void
    {
        $dto = new RegisterRequest(email: 'vi@example.com', password: 'password123', firstName: 'A', lastName: 'B');

        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');
        $this->jwtManager->method('create')->willReturn('jwt');

        $result = $this->sut->register($dto);

        self::assertSame('vi', $result['user']['locale']);
    }

    // --- login ---

    #[Test]
    public function testLoginSuccessReturnsAuthResponse(): void
    {
        $dto = new LoginRequest(email: 'user@example.com', password: 'correctpassword');

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword('hashed');
        $user->setFirstName('Jane');
        $user->setLastName('Doe');

        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(true);
        $this->jwtManager->method('create')->willReturn('jwt_token');

        $result = $this->sut->login($dto);

        self::assertArrayHasKey('user', $result);
        self::assertArrayHasKey('token', $result);
        self::assertArrayHasKey('refreshToken', $result);
        self::assertSame('jwt_token', $result['token']);
        self::assertSame('user@example.com', $result['user']['email']);
    }

    #[Test]
    public function testLoginWithNonExistentEmailThrows401(): void
    {
        $dto = new LoginRequest(email: 'nobody@example.com', password: 'password');
        $this->userRepository->method('findByEmail')->willReturn(null);

        try {
            $this->sut->login($dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('INVALID_CREDENTIALS', $e->errorCode);
        }
    }

    #[Test]
    public function testLoginWithNullPasswordThrows401(): void
    {
        $dto = new LoginRequest(email: 'fb@example.com', password: 'password');

        $user = new User();
        $user->setEmail('fb@example.com');

        $this->userRepository->method('findByEmail')->willReturn($user);

        try {
            $this->sut->login($dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('INVALID_CREDENTIALS', $e->errorCode);
        }
    }

    #[Test]
    public function testLoginWithWrongPasswordThrows401(): void
    {
        $dto = new LoginRequest(email: 'user@example.com', password: 'wrongpassword');

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword('hashed');
        $user->setFirstName('A');
        $user->setLastName('B');

        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('isPasswordValid')->willReturn(false);

        try {
            $this->sut->login($dto);
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('INVALID_CREDENTIALS', $e->errorCode);
        }
    }

    // --- refresh ---

    #[Test]
    public function testRefreshSuccessRotatesTokenAndReturnsNewPair(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setFirstName('A');
        $user->setLastName('B');

        $oldToken = new RefreshToken($user, 'old_token_value', new \DateTimeImmutable('+30 days'));

        $this->refreshTokenRepository->method('findValidByToken')
            ->with('old_token_value')
            ->willReturn($oldToken);

        $this->em->expects(self::once())->method('remove')->with($oldToken);
        $this->jwtManager->method('create')->willReturn('new_jwt');

        $result = $this->sut->refresh('old_token_value');

        self::assertArrayHasKey('token', $result);
        self::assertArrayHasKey('refreshToken', $result);
        self::assertSame('new_jwt', $result['token']);
        self::assertIsString($result['refreshToken']);
        self::assertSame(128, strlen($result['refreshToken']));
    }

    #[Test]
    public function testRefreshWithInvalidTokenThrows401(): void
    {
        $this->refreshTokenRepository->method('findValidByToken')->willReturn(null);

        try {
            $this->sut->refresh('invalid_token');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('REFRESH_TOKEN_EXPIRED', $e->errorCode);
        }
    }

    // --- serializeUser ---

    #[Test]
    public function testSerializeUserReturnsExpectedStructure(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setLocale(UserLocale::EN);
        $user->setAvatarUrl('https://example.com/avatar.jpg');

        $result = AuthService::serializeUser($user);

        self::assertSame('test@example.com', $result['email']);
        self::assertSame('John', $result['firstName']);
        self::assertSame('Doe', $result['lastName']);
        self::assertSame('en', $result['locale']);
        self::assertSame('https://example.com/avatar.jpg', $result['avatarUrl']);
        self::assertIsString($result['id']);
        self::assertCount(6, $result);
    }

    #[Test]
    public function testSerializeUserWithNullAvatar(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('A');
        $user->setLastName('B');

        $result = AuthService::serializeUser($user);

        self::assertNull($result['avatarUrl']);
    }
}