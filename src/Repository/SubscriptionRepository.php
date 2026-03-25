<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Shop;
use App\Entity\Subscription;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
final class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findByShop(Shop $shop): ?Subscription
    {
        return $this->findOneBy(['shop' => $shop]);
    }

    /**
     * @return Subscription[]
     */
    public function findOverdueProSubscriptions(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.plan = :plan')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate < :now')
            ->setParameter('plan', SubscriptionPlan::PRO)
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Subscription[]
     */
    public function findOverdueTrials(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.trialEndsAt IS NOT NULL')
            ->andWhere('s.trialEndsAt < :now')
            ->andWhere('s.endDate IS NULL')
            ->andWhere('s.plan = :plan')
            ->andWhere('s.status = :status')
            ->setParameter('plan', SubscriptionPlan::PRO)
            ->setParameter('status', SubscriptionStatus::ACTIVE)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function resetCountersBefore(\DateTimeImmutable $firstDayOfMonth): int
    {
        return (int) $this->createQueryBuilder('s')
            ->update()
            ->set('s.monthlyAppointmentCount', 0)
            ->set('s.countResetAt', ':resetAt')
            ->where('s.countResetAt < :firstDay')
            ->setParameter('resetAt', $firstDayOfMonth)
            ->setParameter('firstDay', $firstDayOfMonth)
            ->getQuery()
            ->execute();
    }

    public function incrementAppointmentCount(Subscription $subscription): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->executeStatement(
                'UPDATE subscriptions SET monthly_appointment_count = monthly_appointment_count + 1, updated_at = NOW() WHERE id = :id',
                ['id' => $subscription->getId()->toRfc4122()],
            );
    }

    public function decrementAppointmentCount(Subscription $subscription): void
    {
        $this->getEntityManager()
            ->getConnection()
            ->executeStatement(
                'UPDATE subscriptions SET monthly_appointment_count = GREATEST(monthly_appointment_count - 1, 0), updated_at = NOW() WHERE id = :id',
                ['id' => $subscription->getId()->toRfc4122()],
            );
    }
}
