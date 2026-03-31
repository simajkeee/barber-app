<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Service;

use App\Auth\Enum\UserLocale;
use App\Auth\Message\SendPasswordResetEmailMessage;
use App\Auth\Service\PasswordResetService;
use App\Common\Exception\ApiException;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(PasswordResetService::class)]
final class PasswordResetServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private PasswordResetTokenRepository&MockObject $passwordResetTokenRepository;
    private RefreshTokenRepository&MockObject $refreshTokenRepository;
    private EntityManagerInterface&MockObject $em;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private MessageBusInterface&MockObject $messageBus;
    private PasswordResetService $sut;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepository::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->sut = new PasswordResetService(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->refreshTokenRepository,
            $this->em,
            $this->passwordHasher,
            $this->messageBus,
        );
    }

    private function createUser(string $email = 'user@example.com', ?string $password = 'hashed', UserLocale $locale = UserLocale::EN): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($password);
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setLocale($locale);

        return $user;
    }

    // --- requestReset ---

    #[Test]
    public function testRequestResetWithValidUserCreatesTokenAndDispatchesMessage(): void
    {
        $user = $this->createUser('user@example.com', 'hashed', UserLocale::VI);

        $this->userRepository->method('findByEmail')
            ->with('user@example.com')
            ->willReturn($user);

        $this->passwordResetTokenRepository->expects(self::once())
            ->method('invalidateUnusedTokensForUser')
            ->with($user->getId());

        $this->em->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(PasswordResetToken::class));

        $this->em->expects(self::once())->method('flush');

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (SendPasswordResetEmailMessage $message): bool {
                self::assertSame('user@example.com', $message->email);
                self::assertSame(64, \strlen($message->rawToken));
                self::assertTrue(ctype_xdigit($message->rawToken));
                self::assertSame('vi', $message->locale);
                self::assertSame('John', $message->firstName);

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $this->sut->requestReset('user@example.com');
    }

    #[Test]
    public function testRequestResetWithNonExistentEmailDoesNothing(): void
    {
        $this->userRepository->method('findByEmail')->willReturn(null);

        $this->passwordResetTokenRepository->expects(self::never())->method('invalidateUnusedTokensForUser');
        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->sut->requestReset('nobody@example.com');
    }

    #[Test]
    public function testRequestResetWithFacebookOnlyUserDoesNothing(): void
    {
        $user = $this->createUser('fb@example.com', null);

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->passwordResetTokenRepository->expects(self::never())->method('invalidateUnusedTokensForUser');
        $this->em->expects(self::never())->method('persist');
        $this->em->expects(self::never())->method('flush');
        $this->messageBus->expects(self::never())->method('dispatch');

        $this->sut->requestReset('fb@example.com');
    }

    #[Test]
    public function testRequestResetInvalidatesPreviousTokensBeforeCreatingNew(): void
    {
        $user = $this->createUser();
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $callOrder = [];

        $this->passwordResetTokenRepository->expects(self::once())
            ->method('invalidateUnusedTokensForUser')
            ->willReturnCallback(function () use (&$callOrder): void {
                $callOrder[] = 'invalidate';
            });

        $this->em->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function () use (&$callOrder): void {
                $callOrder[] = 'persist';
            });

        $this->sut->requestReset('user@example.com');

        self::assertSame(['invalidate', 'persist'], $callOrder);
    }

    #[Test]
    public function testRequestResetTokenHashMatchesRawToken(): void
    {
        $user = $this->createUser();
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $capturedToken = null;
        $capturedRawToken = null;

        $this->em->expects(self::once())
            ->method('persist')
            ->willReturnCallback(function (PasswordResetToken $token) use (&$capturedToken): void {
                $capturedToken = $token;
            });

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (SendPasswordResetEmailMessage $message) use (&$capturedRawToken): Envelope {
                $capturedRawToken = $message->rawToken;

                return new Envelope(new \stdClass());
            });

        $this->sut->requestReset('user@example.com');

        self::assertNotNull($capturedToken);
        self::assertNotNull($capturedRawToken);
        self::assertSame(hash('sha256', $capturedRawToken), $capturedToken->getTokenHash());
    }

    // --- resetPassword ---

    #[Test]
    public function testResetPasswordWithValidTokenUpdatesPasswordAndDeletesRefreshTokens(): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $user = $this->createUser();

        $resetToken = new PasswordResetToken($user, $tokenHash, new \DateTimeImmutable('+1 hour'));

        $this->passwordResetTokenRepository->method('findByTokenHash')
            ->with($tokenHash)
            ->willReturn($resetToken);

        $this->passwordHasher->expects(self::once())
            ->method('hashPassword')
            ->with($user, 'newpassword123')
            ->willReturn('new_hashed_password');

        $this->refreshTokenRepository->expects(self::once())
            ->method('deleteByUser')
            ->with($user);

        $this->em->expects(self::once())->method('flush');

        $this->sut->resetPassword($rawToken, 'newpassword123');

        self::assertSame('new_hashed_password', $user->getPassword());
        self::assertTrue($resetToken->isUsed());
    }

    #[Test]
    public function testResetPasswordWithInvalidTokenThrows400(): void
    {
        $rawToken = bin2hex(random_bytes(32));

        $this->passwordResetTokenRepository->method('findByTokenHash')->willReturn(null);

        try {
            $this->sut->resetPassword($rawToken, 'newpassword123');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('INVALID_RESET_TOKEN', $e->errorCode);
        }
    }

    #[Test]
    public function testResetPasswordWithExpiredTokenThrows400(): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $user = $this->createUser();

        $resetToken = new PasswordResetToken($user, $tokenHash, new \DateTimeImmutable('-1 minute'));

        $this->passwordResetTokenRepository->method('findByTokenHash')
            ->with($tokenHash)
            ->willReturn($resetToken);

        $this->passwordHasher->expects(self::never())->method('hashPassword');
        $this->em->expects(self::never())->method('flush');

        try {
            $this->sut->resetPassword($rawToken, 'newpassword123');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('INVALID_RESET_TOKEN', $e->errorCode);
        }
    }

    #[Test]
    public function testResetPasswordWithUsedTokenThrows400(): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $user = $this->createUser();

        $resetToken = new PasswordResetToken($user, $tokenHash, new \DateTimeImmutable('+1 hour'));
        $resetToken->markUsed();

        $this->passwordResetTokenRepository->method('findByTokenHash')
            ->with($tokenHash)
            ->willReturn($resetToken);

        $this->passwordHasher->expects(self::never())->method('hashPassword');
        $this->em->expects(self::never())->method('flush');

        try {
            $this->sut->resetPassword($rawToken, 'newpassword123');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->statusCode);
            self::assertSame('INVALID_RESET_TOKEN', $e->errorCode);
        }
    }
}
