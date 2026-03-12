<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Enum\UserLocale;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FacebookAuthService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly AuthService $authService,
        private readonly string $facebookGraphUrl,
    ) {
    }

    public function authenticate(string $facebookAccessToken): array
    {
        $profile = $this->fetchFacebookProfile($facebookAccessToken);

        // 1. Existing user by Facebook ID
        $user = $this->userRepository->findByFacebookId($profile['id']);
        if ($user !== null) {
            return ['isNewUser' => false] + $this->authService->buildAuthResponse($user);
        }

        // 2. Existing user by email — link Facebook account
        if (isset($profile['email'])) {
            $user = $this->userRepository->findByEmail($profile['email']);
            if ($user !== null) {
                $user->setFacebookId($profile['id']);
                if ($user->getAvatarUrl() === null && isset($profile['picture']['data']['url'])) {
                    $user->setAvatarUrl($profile['picture']['data']['url']);
                }
                return ['isNewUser' => false] + $this->authService->buildAuthResponse($user);
            }
        }

        // 3. New user
        $user = new User();
        $user->setEmail($profile['email'] ?? $profile['id'] . '@facebook.placeholder');
        $user->setFirstName($profile['first_name'] ?? '');
        $user->setLastName($profile['last_name'] ?? '');
        $user->setFacebookId($profile['id']);
        $user->setLocale(UserLocale::VI);
        if (isset($profile['picture']['data']['url'])) {
            $user->setAvatarUrl($profile['picture']['data']['url']);
        }

        $this->em->persist($user);

        try {
            return ['isNewUser' => true] + $this->authService->buildAuthResponse($user);
        } catch (UniqueConstraintViolationException) {
            $this->em->clear();

            $existing = $this->userRepository->findByEmail($profile['email'] ?? '')
                ?? $this->userRepository->findByFacebookId($profile['id']);

            if ($existing === null) {
                throw new ApiException('FACEBOOK_AUTH_FAILED', 'Facebook authentication failed. Please try again.', 500);
            }

            return ['isNewUser' => false] + $this->authService->buildAuthResponse($existing);
        }
    }

    private function fetchFacebookProfile(string $accessToken): array
    {
        $response = $this->httpClient->request('GET', $this->facebookGraphUrl . '/me', [
            'query' => [
                'fields' => 'id,email,first_name,last_name,picture.type(large)',
                'access_token' => $accessToken,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException('INVALID_FACEBOOK_TOKEN', 'Invalid or expired Facebook access token.', 401);
        }

        $data = $response->toArray(false);

        if (!isset($data['id'])) {
            throw new ApiException('INVALID_FACEBOOK_TOKEN', 'Could not retrieve Facebook profile.', 401);
        }

        return $data;
    }
}