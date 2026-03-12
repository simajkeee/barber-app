<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Service;

use App\Auth\Enum\UserLocale;
use App\Auth\Service\AuthService;
use App\Auth\Service\FacebookAuthService;
use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(FacebookAuthService::class)]
final class FacebookAuthServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private EntityManagerInterface&MockObject $em;
    private AuthService&MockObject $authService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->authService = $this->createMock(AuthService::class);
    }

    private function createService(MockResponse $response): FacebookAuthService
    {
        return new FacebookAuthService(
            new MockHttpClient($response),
            $this->userRepository,
            $this->em,
            $this->authService,
            'https://graph.facebook.com/v18.0',
        );
    }

    private function facebookProfileResponse(
        string $id = '123456',
        ?string $email = 'fb@example.com',
        string $firstName = 'Mark',
        string $lastName = 'Zuck',
    ): MockResponse {
        $data = [
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'picture' => ['data' => ['url' => 'https://fb.com/pic.jpg']],
        ];
        if ($email !== null) {
            $data['email'] = $email;
        }

        return new MockResponse(json_encode($data), ['http_code' => 200]);
    }

    // --- Existing user by Facebook ID ---

    #[Test]
    public function testAuthenticateExistingUserByFacebookIdReturnsNotNew(): void
    {
        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setFirstName('A');
        $user->setLastName('B');
        $user->setFacebookId('123456');

        $this->userRepository->method('findByFacebookId')->with('123456')->willReturn($user);

        $authResponse = ['user' => [], 'token' => 'jwt', 'refreshToken' => 'rt'];
        $this->authService->method('buildAuthResponse')->with($user)->willReturn($authResponse);

        $sut = $this->createService($this->facebookProfileResponse());
        $result = $sut->authenticate('valid_fb_token');

        self::assertFalse($result['isNewUser']);
        self::assertSame('jwt', $result['token']);
    }

    // --- Existing user by email — link Facebook account ---

    #[Test]
    public function testAuthenticateExistingUserByEmailLinksFacebookId(): void
    {
        $user = new User();
        $user->setEmail('fb@example.com');
        $user->setFirstName('A');
        $user->setLastName('B');

        $this->userRepository->method('findByFacebookId')->willReturn(null);
        $this->userRepository->method('findByEmail')->with('fb@example.com')->willReturn($user);

        $authResponse = ['user' => [], 'token' => 'jwt', 'refreshToken' => 'rt'];
        $this->authService->method('buildAuthResponse')->willReturn($authResponse);

        $sut = $this->createService($this->facebookProfileResponse());
        $result = $sut->authenticate('valid_fb_token');

        self::assertFalse($result['isNewUser']);
        self::assertSame('123456', $user->getFacebookId());
        self::assertSame('https://fb.com/pic.jpg', $user->getAvatarUrl());
    }

    #[Test]
    public function testAuthenticateLinkDoesNotOverwriteExistingAvatar(): void
    {
        $user = new User();
        $user->setEmail('fb@example.com');
        $user->setFirstName('A');
        $user->setLastName('B');
        $user->setAvatarUrl('https://existing-avatar.com/pic.jpg');

        $this->userRepository->method('findByFacebookId')->willReturn(null);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->authService->method('buildAuthResponse')->willReturn(['user' => [], 'token' => 'jwt', 'refreshToken' => 'rt']);

        $sut = $this->createService($this->facebookProfileResponse());
        $sut->authenticate('valid_fb_token');

        self::assertSame('https://existing-avatar.com/pic.jpg', $user->getAvatarUrl());
    }

    // --- New user creation ---

    #[Test]
    public function testAuthenticateNewUserCreatesAndPersists(): void
    {
        $this->userRepository->method('findByFacebookId')->willReturn(null);
        $this->userRepository->method('findByEmail')->willReturn(null);

        $this->em->expects(self::once())->method('persist')
            ->willReturnCallback(function (User $user): void {
                self::assertSame('fb@example.com', $user->getEmail());
                self::assertSame('Mark', $user->getFirstName());
                self::assertSame('Zuck', $user->getLastName());
                self::assertSame('123456', $user->getFacebookId());
                self::assertSame(UserLocale::VI, $user->getLocale());
            });

        $this->authService->method('buildAuthResponse')->willReturn(['user' => [], 'token' => 'jwt', 'refreshToken' => 'rt']);

        $sut = $this->createService($this->facebookProfileResponse());
        $result = $sut->authenticate('valid_fb_token');

        self::assertTrue($result['isNewUser']);
    }

    #[Test]
    public function testAuthenticateNewUserWithoutEmailUsesPlaceholder(): void
    {
        $this->userRepository->method('findByFacebookId')->willReturn(null);

        $this->em->expects(self::once())->method('persist')
            ->willReturnCallback(function (User $user): void {
                self::assertSame('123456@facebook.placeholder', $user->getEmail());
            });

        $this->authService->method('buildAuthResponse')->willReturn(['user' => [], 'token' => 'jwt', 'refreshToken' => 'rt']);

        $sut = $this->createService($this->facebookProfileResponse(email: null));
        $sut->authenticate('valid_fb_token');
    }

    // --- Invalid Facebook token ---

    #[Test]
    public function testAuthenticateInvalidTokenThrows401(): void
    {
        $response = new MockResponse('{"error": "invalid"}', ['http_code' => 401]);
        $sut = $this->createService($response);

        try {
            $sut->authenticate('bad_token');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('INVALID_FACEBOOK_TOKEN', $e->errorCode);
        }
    }

    #[Test]
    public function testAuthenticateResponseWithoutIdThrows401(): void
    {
        $response = new MockResponse(json_encode(['email' => 'no-id@example.com']), ['http_code' => 200]);
        $sut = $this->createService($response);

        try {
            $sut->authenticate('token_no_id');
            self::fail('Expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(401, $e->statusCode);
            self::assertSame('INVALID_FACEBOOK_TOKEN', $e->errorCode);
        }
    }

    // --- No double flush ---

    #[Test]
    public function testAuthenticateNewUserDoesNotFlushBeforeBuildAuthResponse(): void
    {
        $this->userRepository->method('findByFacebookId')->willReturn(null);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->authService->method('buildAuthResponse')->willReturn(['user' => [], 'token' => 'jwt', 'refreshToken' => 'rt']);

        $this->em->expects(self::never())->method('flush');

        $sut = $this->createService($this->facebookProfileResponse());
        $sut->authenticate('valid_fb_token');
    }
}