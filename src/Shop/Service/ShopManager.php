<?php

declare(strict_types=1);

namespace App\Shop\Service;

use App\Common\Exception\ApiException;
use App\Entity\Shop;
use App\Entity\User;
use App\Entity\WorkSchedule;
use App\Repository\ShopRepository;
use App\Repository\WorkScheduleRepository;
use App\Shop\Dto\CreateShopRequest;
use App\Shop\Dto\UpdateScheduleRequest;
use App\Shop\Dto\UpdateShopRequest;
use App\Shop\Enum\DayOfWeek;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ShopManager
{
    public function __construct(
        private readonly ShopRepository $shopRepository,
        private readonly WorkScheduleRepository $workScheduleRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function createShop(User $user, CreateShopRequest $dto): Shop
    {
        if ($this->shopRepository->findByOwner($user) !== null) {
            throw new ApiException('SHOP_ALREADY_EXISTS', 'You already have a shop.', 409);
        }

        $shop = new Shop();
        $shop->setOwner($user);
        $shop->setName($dto->name);
        $shop->setAddress($dto->address);
        $shop->setPhone($dto->phone);
        $shop->setDescription($dto->description);
        $shop->setSlug($this->generateSlug($dto->name));

        $this->em->persist($shop);

        $this->createDefaultSchedule($shop);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('SHOP_ALREADY_EXISTS', 'You already have a shop.', 409);
        }

        return $shop;
    }

    /**
     * @param string[] $explicitFields Keys explicitly present in the request body
     */
    public function updateShop(Shop $shop, UpdateShopRequest $dto, array $explicitFields): Shop
    {
        if ($dto->name !== null) {
            $shop->setName($dto->name);
        }
        if ($dto->address !== null) {
            $shop->setAddress($dto->address);
        }
        if ($dto->phone !== null) {
            $shop->setPhone($dto->phone);
        }
        if (in_array('description', $explicitFields, true)) {
            $shop->setDescription($dto->description);
        }
        if (in_array('coverImageUrl', $explicitFields, true)) {
            $shop->setCoverImageUrl($dto->coverImageUrl);
        }

        if ($dto->slug !== null) {
            $existing = $this->shopRepository->findBySlug($dto->slug);
            if ($existing !== null && $existing->getId()->toRfc4122() !== $shop->getId()->toRfc4122()) {
                throw new ApiException('SLUG_ALREADY_EXISTS', 'This slug is already taken.', 409);
            }
            $shop->setSlug($dto->slug);
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('SLUG_ALREADY_EXISTS', 'This slug is already taken.', 409);
        }

        return $shop;
    }

    /**
     * @return WorkSchedule[]
     */
    public function updateSchedule(Shop $shop, UpdateScheduleRequest $dto): array
    {
        $this->validateSchedule($dto);

        $this->em->wrapInTransaction(function () use ($shop, $dto, &$schedules): void {
            $this->workScheduleRepository->deleteByShop($shop);

            $schedules = [];
            foreach ($dto->schedule as $entry) {
                $schedule = new WorkSchedule();
                $schedule->setShop($shop);
                $schedule->setDayOfWeek($entry->dayOfWeek);
                $schedule->setIsOpen($entry->isOpen);

                if ($entry->isOpen) {
                    $schedule->setOpenTime(new \DateTimeImmutable($entry->openTime));
                    $schedule->setCloseTime(new \DateTimeImmutable($entry->closeTime));
                }

                $this->em->persist($schedule);
                $schedules[] = $schedule;
            }

            $this->em->flush();
        });

        return $schedules;
    }

    public function generateSlug(string $name): string
    {
        $slugger = new AsciiSlugger('vi');
        $slug = strtolower((string) $slugger->slug($name));

        if ($this->shopRepository->findBySlug($slug) === null) {
            return $slug;
        }

        $suffix = bin2hex(random_bytes(2));

        return $slug . '-' . $suffix;
    }

    public function getShopForUser(User $user): ?Shop
    {
        return $this->shopRepository->findByOwner($user);
    }

    /**
     * @return WorkSchedule[]
     */
    public function getSchedule(Shop $shop): array
    {
        return $this->workScheduleRepository->findByShop($shop);
    }

    /**
     * @param WorkSchedule[] $schedules
     * @return array<string, mixed>
     */
    public static function serializeShop(Shop $shop, array $schedules): array
    {
        return [
            'id' => (string) $shop->getId(),
            'name' => $shop->getName(),
            'address' => $shop->getAddress(),
            'phone' => $shop->getPhone(),
            'description' => $shop->getDescription(),
            'slug' => $shop->getSlug(),
            'coverImageUrl' => $shop->getCoverImageUrl(),
            'schedule' => array_map(self::serializeScheduleEntry(...), $schedules),
            'createdAt' => $shop->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $shop->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeScheduleEntry(WorkSchedule $schedule): array
    {
        return [
            'dayOfWeek' => $schedule->getDayOfWeek()->value,
            'openTime' => $schedule->getOpenTime()?->format('H:i'),
            'closeTime' => $schedule->getCloseTime()?->format('H:i'),
            'isOpen' => $schedule->isOpen(),
        ];
    }

    private function createDefaultSchedule(Shop $shop): void
    {
        foreach (DayOfWeek::cases() as $day) {
            $schedule = new WorkSchedule();
            $schedule->setShop($shop);
            $schedule->setDayOfWeek($day);

            if ($day === DayOfWeek::SUNDAY) {
                $schedule->setIsOpen(false);
            } else {
                $schedule->setIsOpen(true);
                $schedule->setOpenTime(new \DateTimeImmutable('09:00'));
                $schedule->setCloseTime(new \DateTimeImmutable('19:00'));
            }

            $this->em->persist($schedule);
        }
    }

    private function validateSchedule(UpdateScheduleRequest $dto): void
    {
        $days = [];
        foreach ($dto->schedule as $entry) {
            $day = $entry->dayOfWeek->value;
            if (isset($days[$day])) {
                throw new ApiException('VALIDATION_ERROR', 'Duplicate day in schedule.', 400);
            }
            $days[$day] = true;

            if ($entry->isOpen) {
                if ($entry->openTime === null || $entry->closeTime === null) {
                    throw new ApiException('VALIDATION_ERROR', 'Opening and closing times are required when the shop is open.', 400);
                }
                if ($entry->openTime >= $entry->closeTime) {
                    throw new ApiException('VALIDATION_ERROR', 'Opening time must be before closing time.', 400);
                }
            }
        }

        if (count($days) !== 7) {
            throw new ApiException('VALIDATION_ERROR', 'All 7 days of the week are required.', 400);
        }
    }
}