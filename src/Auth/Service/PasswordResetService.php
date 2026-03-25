<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Message\SendPasswordResetEmailMessage;
use App\Common\Exception\ApiException;
use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetService
{
    private const TOKEN_TTL_HOURS = 1;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordResetTokenRepository $passwordResetTokenRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function requestReset(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        if (null === $user || null === $user->getPassword()) {
            return;
        }

        $this->passwordResetTokenRepository->invalidateUnusedTokensForUser($user->getId());

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = new \DateTimeImmutable('+'.self::TOKEN_TTL_HOURS.' hour');

        $resetToken = new PasswordResetToken($user, $tokenHash, $expiresAt);
        $this->em->persist($resetToken);
        $this->em->flush();

        $this->messageBus->dispatch(new SendPasswordResetEmailMessage(
            $user->getEmail(),
            $rawToken,
            $user->getLocale()->value,
        ));
    }

    public function resetPassword(string $rawToken, string $newPassword): void
    {
        $tokenHash = hash('sha256', $rawToken);
        $resetToken = $this->passwordResetTokenRepository->findByTokenHash($tokenHash);

        if (null === $resetToken || $resetToken->isUsed() || $resetToken->isExpired()) {
            throw new ApiException('INVALID_RESET_TOKEN', 'This reset link is invalid or has expired.', 400);
        }

        $user = $resetToken->getUser();

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $resetToken->markUsed();

        $this->refreshTokenRepository->deleteByUser($user);

        $this->em->flush();
    }
}
