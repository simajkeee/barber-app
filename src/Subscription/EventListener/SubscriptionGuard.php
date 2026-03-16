<?php

declare(strict_types=1);

namespace App\Subscription\EventListener;

use App\Entity\User;
use App\Subscription\Enum\SubscriptionStatus;
use App\Subscription\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SubscriptionGuard
{
    private const array EXCLUDED_PREFIXES = [
        '/api/v1/auth/',
        '/api/v1/admin/',
        '/api/v1/subscription',
        '/api/v1/public/',
    ];

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getMethod();

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        $shop = $user->getShop();
        if ($shop === null) {
            return;
        }

        try {
            $subscription = $this->subscriptionService->getByShop($shop);
        } catch (\Throwable) {
            return;
        }

        if ($subscription->getStatus() === SubscriptionStatus::CANCELLED) {
            $event->setResponse(new JsonResponse([
                'code' => 'SUBSCRIPTION_CANCELLED',
                'message' => 'Your subscription has been cancelled. Contact support for assistance.',
            ], 403));
        }
    }
}
