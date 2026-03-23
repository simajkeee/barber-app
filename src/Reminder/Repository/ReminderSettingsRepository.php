<?php

declare(strict_types=1);

namespace App\Reminder\Repository;

use App\Entity\Shop;
use App\Reminder\Entity\ReminderSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReminderSettings>
 */
final class ReminderSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReminderSettings::class);
    }

    public function findByShop(Shop $shop): ?ReminderSettings
    {
        return $this->findOneBy(['shop' => $shop]);
    }

    public function findByShopAndLocale(Shop $shop, string $locale): ?ReminderSettings
    {
        return $this->findOneBy(['shop' => $shop, 'locale' => $locale]);
    }
}
