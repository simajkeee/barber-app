<?php

declare(strict_types=1);

namespace App\Reminder\Service;

use App\Entity\Shop;
use App\Reminder\Dto\UpdateReminderSettingsRequest;
use App\Reminder\Entity\ReminderSettings;
use App\Reminder\Repository\ReminderSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ReminderSettingsService
{
    public function __construct(
        private readonly ReminderSettingsRepository $reminderSettingsRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getSettings(Shop $shop): ReminderSettings
    {
        $settings = $this->reminderSettingsRepository->findByShop($shop);

        if (null === $settings) {
            $settings = new ReminderSettings();
            $settings->setShop($shop);

            $this->em->persist($settings);
            $this->em->flush();
        }

        return $settings;
    }

    public function updateSettings(Shop $shop, UpdateReminderSettingsRequest $dto): ReminderSettings
    {
        $settings = $this->getSettings($shop);

        if (null !== $dto->daysSinceLastVisit) {
            $settings->setDaysSinceLastVisit($dto->daysSinceLastVisit);
        }

        if (null !== $dto->messageTemplate) {
            $settings->setMessageTemplate($dto->messageTemplate);
        }

        $this->em->flush();

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeSettings(ReminderSettings $settings): array
    {
        return [
            'daysSinceLastVisit' => $settings->getDaysSinceLastVisit(),
            'messageTemplate' => $settings->getMessageTemplate(),
        ];
    }
}
