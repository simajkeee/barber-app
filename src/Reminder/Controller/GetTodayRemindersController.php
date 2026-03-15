<?php

declare(strict_types=1);

namespace App\Reminder\Controller;

use App\Common\Exception\ApiException;
use App\Entity\User;
use App\Reminder\Dto\ReminderTodayQuery;
use App\Reminder\Service\ReminderService;
use App\Shop\Service\ShopManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class GetTodayRemindersController
{
    public function __construct(
        private ShopManager $shopManager,
        private ReminderService $reminderService,
    ) {
    }

    #[Route('/today', methods: ['GET'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapQueryString] ?ReminderTodayQuery $query = null,
    ): JsonResponse {
        $shop = $this->shopManager->getShopForUser($user);
        if (null === $shop) {
            throw new ApiException('SHOP_NOT_FOUND', 'Shop not found. Create one first.', 404);
        }

        $result = $this->reminderService->getTodayReminders($shop, $query ?? new ReminderTodayQuery());

        return new JsonResponse($result);
    }
}
