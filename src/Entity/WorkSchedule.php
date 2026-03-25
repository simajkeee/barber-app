<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WorkScheduleRepository;
use App\Shop\Enum\DayOfWeek;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkScheduleRepository::class)]
#[ORM\Table(name: 'work_schedules')]
#[ORM\UniqueConstraint(name: 'uniq_schedule_shop_day', columns: ['shop_id', 'day_of_week'])]
class WorkSchedule
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Shop $shop;

    #[ORM\Column(length: 10, enumType: DayOfWeek::class)]
    private DayOfWeek $dayOfWeek;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $openTime = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $closeTime = null;

    #[ORM\Column]
    private bool $isOpen = true;

    public function __construct()
    {
        $this->id = Uuid::v7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function setShop(Shop $shop): void
    {
        $this->shop = $shop;
    }

    public function getDayOfWeek(): DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(DayOfWeek $dayOfWeek): void
    {
        $this->dayOfWeek = $dayOfWeek;
    }

    public function getOpenTime(): ?\DateTimeImmutable
    {
        return $this->openTime;
    }

    public function setOpenTime(?\DateTimeImmutable $openTime): void
    {
        $this->openTime = $openTime;
    }

    public function getCloseTime(): ?\DateTimeImmutable
    {
        return $this->closeTime;
    }

    public function setCloseTime(?\DateTimeImmutable $closeTime): void
    {
        $this->closeTime = $closeTime;
    }

    public function isOpen(): bool
    {
        return $this->isOpen;
    }

    public function setIsOpen(bool $isOpen): void
    {
        $this->isOpen = $isOpen;
    }
}
