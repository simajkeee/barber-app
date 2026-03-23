<?php

declare(strict_types=1);

namespace App\Reminder\Service;

use App\Entity\Shop;
use App\Reminder\Dto\UpdateReminderSettingsRequest;
use App\Reminder\Entity\ReminderSettings;
use App\Reminder\Repository\ReminderSettingsRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class ReminderSettingsService
{
    public function __construct(
        private readonly ReminderSettingsRepository $reminderSettingsRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getSettings(Shop $shop, string $locale = 'vi'): ReminderSettings
    {
        $settings = $this->reminderSettingsRepository->findByShopAndLocale($shop, $locale);

        if (null === $settings) {
            $settings = $this->createDefaultSettings($shop, $locale);
            $this->em->persist($settings);
            try {
                $this->em->flush();
            } catch (UniqueConstraintViolationException) {
                // Concurrent request already created the record — fetch and return it
                $this->em->clear();
                $settings = $this->reminderSettingsRepository->findByShopAndLocale($shop, $locale);
                if (null === $settings) {
                    throw new \RuntimeException(sprintf('Failed to load reminder settings for shop %s locale %s after concurrent insert.', $shop->getId(), $locale));
                }
            }
        }

        return $settings;
    }

    public function updateSettings(Shop $shop, UpdateReminderSettingsRequest $dto): ReminderSettings
    {
        $locale = $dto->locale ?? 'vi';
        $settings = $this->getSettings($shop, $locale);

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
            'locale' => $settings->getLocale(),
        ];
    }

    private function createDefaultSettings(Shop $shop, string $locale): ReminderSettings
    {
        $settings = new ReminderSettings();
        $settings->setShop($shop);
        $settings->setLocale($locale);

        if ('en' === $locale) {
            $settings->setMessageTemplate(ReminderSettings::DEFAULT_MESSAGE_TEMPLATE_EN);
        }

        return $settings;
    }
}
