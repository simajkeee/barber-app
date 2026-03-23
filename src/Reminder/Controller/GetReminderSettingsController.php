<?php

declare(strict_types=1);

namespace App\Reminder\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Reminder\Service\ReminderSettingsService;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class GetReminderSettingsController
{
    public function __construct(
        private ShopManager $shopManager,
        private ReminderSettingsService $reminderSettingsService,
    ) {
    }

    #[Route('/settings', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user, Request $request): JsonResponse
    {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $locale = $request->query->getString('locale') ?: $user->getLocale()->value;

        $settings = $this->reminderSettingsService->getSettings($shop, $locale);

        return new JsonResponse(ReminderSettingsService::serializeSettings($settings));
    }
}
