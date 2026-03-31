<?php

declare(strict_types=1);

namespace App\Subscription\Service;

use App\Entity\Shop;
use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use App\Subscription\Enum\SubscriptionPlan;
use App\Subscription\Enum\SubscriptionStatus;
use Doctrine\ORM\EntityManagerInterface;

final class SubscriptionService
{
    public const int FREE_APPOINTMENT_LIMIT = 50;
    public const int DEFAULT_PRO_DURATION_DAYS = 30;
    public const int TRIAL_DURATION_DAYS = 30;
    private const string TZ_NAME = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function createTrialForShop(Shop $shop): Subscription
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = new \DateTimeImmutable('now', $tz);
        $trialEndsAt = $now->modify('+'.self::TRIAL_DURATION_DAYS.' days');

        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setPlan(SubscriptionPlan::PRO);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartDate($now);
        $subscription->setEndDate(null);
        $subscription->setTrialEndsAt($trialEndsAt);
        $subscription->setMonthlyAppointmentCount(0);
        $subscription->setCountResetAt($now->modify('first day of this month')->setTime(0, 0));

        $this->em->persist($subscription);
        $this->em->flush();

        return $subscription;
    }

    private function createFreeForShop(Shop $shop): Subscription
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = new \DateTimeImmutable('now', $tz);

        $subscription = new Subscription();
        $subscription->setShop($shop);
        $subscription->setPlan(SubscriptionPlan::FREE);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartDate($now);
        $subscription->setEndDate(null);
        $subscription->setMonthlyAppointmentCount(0);
        $subscription->setCountResetAt($now->modify('first day of this month')->setTime(0, 0));

        $this->em->persist($subscription);
        $this->em->flush();

        return $subscription;
    }

    public function activateFromPayment(Subscription $subscription, string $momoTransId): void
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = new \DateTimeImmutable('now', $tz);

        $subscription->setPlan(SubscriptionPlan::PRO);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartDate($now);
        $subscription->setEndDate($now->modify('+'.self::DEFAULT_PRO_DURATION_DAYS.' days'));
        $subscription->setMomoTransId($momoTransId);
        $subscription->setRenewalReminderSentAt(null);
    }

    public function activate(Shop $shop, int $durationDays = self::DEFAULT_PRO_DURATION_DAYS): Subscription
    {
        $subscription = $this->getByShop($shop);
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = new \DateTimeImmutable('now', $tz);

        $subscription->setPlan(SubscriptionPlan::PRO);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setStartDate($now);

        if (null !== $subscription->getEndDate() && $subscription->getEndDate() > $now) {
            $subscription->setEndDate($subscription->getEndDate()->modify("+{$durationDays} days"));
        } else {
            $subscription->setEndDate($now->modify("+{$durationDays} days"));
        }

        $this->em->flush();

        return $subscription;
    }

    public function cancel(Shop $shop): Subscription
    {
        $subscription = $this->getByShop($shop);
        $subscription->setStatus(SubscriptionStatus::CANCELLED);

        $this->em->flush();

        return $subscription;
    }

    public function downgrade(Shop $shop): Subscription
    {
        $subscription = $this->getByShop($shop);
        $subscription->setPlan(SubscriptionPlan::FREE);
        $subscription->setStatus(SubscriptionStatus::ACTIVE);
        $subscription->setEndDate(null);

        $this->em->flush();

        return $subscription;
    }

    public function getByShop(Shop $shop): Subscription
    {
        $subscription = $this->subscriptionRepository->findByShop($shop);
        if (null === $subscription) {
            return $this->createFreeForShop($shop);
        }

        return $subscription;
    }

    public function isActive(Shop $shop): bool
    {
        $subscription = $this->getByShop($shop);

        return SubscriptionStatus::CANCELLED !== $subscription->getStatus();
    }

    public function canCreateAppointment(Shop $shop): bool
    {
        $subscription = $this->getByShop($shop);

        if (SubscriptionStatus::CANCELLED === $subscription->getStatus()) {
            return false;
        }

        if (SubscriptionPlan::PRO === $subscription->getPlan()) {
            return true;
        }

        return $subscription->getMonthlyAppointmentCount() < self::FREE_APPOINTMENT_LIMIT;
    }

    public function incrementAppointmentCount(Shop $shop): void
    {
        $this->subscriptionRepository->incrementAppointmentCount($this->getByShop($shop));
    }

    public function decrementAppointmentCount(Shop $shop): void
    {
        $this->subscriptionRepository->decrementAppointmentCount($this->getByShop($shop));
    }

    public function expireOverdueSubscriptions(): int
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = new \DateTimeImmutable('now', $tz);

        $overdue = $this->subscriptionRepository->findOverdueProSubscriptions($now);
        foreach ($overdue as $subscription) {
            $subscription->setStatus(SubscriptionStatus::EXPIRED);
            $subscription->setPlan(SubscriptionPlan::FREE);
            $subscription->setEndDate(null);
        }

        $this->em->flush();

        return \count($overdue);
    }

    /**
     * @return Subscription[]
     */
    public function expireOverdueTrials(): array
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = new \DateTimeImmutable('now', $tz);

        $overdue = $this->subscriptionRepository->findOverdueTrials($now);
        foreach ($overdue as $subscription) {
            $subscription->setPlan(SubscriptionPlan::FREE);
            $subscription->setStatus(SubscriptionStatus::ACTIVE);
            $subscription->setEndDate(null);
            $subscription->setMonthlyAppointmentCount(0);
            $subscription->setCountResetAt($now->modify('first day of this month')->setTime(0, 0));
            // trialEndsAt is preserved as a historical marker
        }

        $this->em->flush();

        return $overdue;
    }

    /**
     * Finds subscriptions expiring ~3 days from now, marks them as reminded,
     * and returns them for email dispatch.
     *
     * @return Subscription[]
     */
    public function sendTrialExpiryReminders(): array
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $now = new \DateTimeImmutable('now', $tz);

        $expiring = $this->subscriptionRepository->findExpiringTrialsSoon($now);
        foreach ($expiring as $subscription) {
            $subscription->setTrialReminderSentAt($now);
        }

        $this->em->flush();

        return $expiring;
    }

    public function resetMonthlyCounters(): int
    {
        $tz = new \DateTimeZone(self::TZ_NAME);
        $firstDayOfMonth = new \DateTimeImmutable('first day of this month midnight', $tz);

        return $this->subscriptionRepository->resetCountersBefore($firstDayOfMonth);
    }
}
