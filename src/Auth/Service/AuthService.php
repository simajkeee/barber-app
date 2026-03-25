<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\RegisterRequest;
use App\Common\Exception\ApiException;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthService
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function register(RegisterRequest $dto): array
    {
        if (null !== $this->userRepository->findByEmail($dto->email)) {
            throw new ApiException('EMAIL_ALREADY_EXISTS', 'A user with this email already exists.', 409);
        }

        $normalizedPhone = $this->normalizePhoneNumber($dto->phoneNumber);

        $digitsOnly = (string) preg_replace('/\D/', '', $normalizedPhone);
        if (\strlen($digitsOnly) < 9 || \strlen($digitsOnly) > 15) {
            throw new ApiException('VALIDATION_ERROR', 'Phone number format is invalid.', 400);
        }

        if (null !== $this->userRepository->findByPhoneNumber($normalizedPhone)) {
            throw new ApiException('PHONE_ALREADY_IN_USE', 'This phone number is already registered.', 422);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setLocale($dto->locale);
        $user->setPhoneNumber($normalizedPhone);

        $this->em->persist($user);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('EMAIL_ALREADY_EXISTS', 'A user with this email already exists.', 409);
        }

        return $this->buildAuthResponse($user);
    }

    public function login(LoginRequest $dto): array
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (null === $user || null === $user->getPassword()) {
            throw new ApiException('INVALID_CREDENTIALS', 'Invalid email or password.', 401);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new ApiException('INVALID_CREDENTIALS', 'Invalid email or password.', 401);
        }

        return $this->buildAuthResponse($user);
    }

    public function refresh(string $refreshToken): array
    {
        $token = $this->refreshTokenRepository->findValidByToken($refreshToken);

        if (null === $token) {
            throw new ApiException('REFRESH_TOKEN_EXPIRED', 'Invalid or expired refresh token.', 401);
        }

        $user = $token->getUser();

        $this->em->remove($token);

        $newToken = $this->createRefreshToken($user);
        $this->em->flush();

        return [
            'token' => $this->jwtManager->create($user),
            'refreshToken' => $newToken,
        ];
    }

    public function buildAuthResponse(User $user): array
    {
        $refreshToken = $this->createRefreshToken($user);
        $this->em->flush();

        return [
            'user' => self::serializeUser($user),
            'token' => $this->jwtManager->create($user),
            'refreshToken' => $refreshToken,
        ];
    }

    public static function serializeUser(User $user): array
    {
        return [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'locale' => $user->getLocale()->value,
            'avatarUrl' => $user->getAvatarUrl(),
            'phoneNumber' => $user->getPhoneNumber(),
        ];
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $phone = (string) preg_replace('/[\s\-\(\)]/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        if (str_starts_with($phone, '84') && 11 === \strlen($phone)) {
            return '+'.$phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+84'.substr($phone, 1);
        }

        return $phone;
    }

    private function createRefreshToken(User $user): string
    {
        $token = bin2hex(random_bytes(64));
        $expiresAt = new \DateTimeImmutable('+'.self::REFRESH_TOKEN_TTL_DAYS.' days');

        $refreshToken = new RefreshToken($user, $token, $expiresAt);
        $this->em->persist($refreshToken);

        return $token;
    }
}
