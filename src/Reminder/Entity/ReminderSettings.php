<?php

declare(strict_types=1);

namespace App\Reminder\Entity;

use App\Entity\Shop;
use App\Reminder\Repository\ReminderSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReminderSettingsRepository::class)]
#[ORM\Table(name: 'reminder_settings')]
#[ORM\UniqueConstraint(name: 'uniq_reminder_settings_shop_locale', columns: ['shop_id', 'locale'])]
#[ORM\HasLifecycleCallbacks]
class ReminderSettings
{
    public const DEFAULT_DAYS_SINCE_LAST_VISIT = 30;
    public const DEFAULT_MESSAGE_TEMPLATE = 'Chào {client_name}! Đã {days_since_visit} ngày kể từ lần cắt tóc cuối tại {shop_name}. Bạn có muốn đặt lịch hẹn mới không? 💈';
    public const DEFAULT_MESSAGE_TEMPLATE_EN = 'Hi {client_name}! It\'s been {days_since_visit} days since your last visit at {shop_name}. Ready to book your next appointment? 💈';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Shop $shop;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 30])]
    private int $daysSinceLastVisit = self::DEFAULT_DAYS_SINCE_LAST_VISIT;

    #[ORM\Column(type: Types::TEXT)]
    private string $messageTemplate = self::DEFAULT_MESSAGE_TEMPLATE;

    #[ORM\Column(length: 5, options: ['default' => 'vi'])]
    private string $locale = 'vi';

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $automatedEmailEnabled = false;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getDaysSinceLastVisit(): int
    {
        return $this->daysSinceLastVisit;
    }

    public function setDaysSinceLastVisit(int $daysSinceLastVisit): void
    {
        $this->daysSinceLastVisit = $daysSinceLastVisit;
    }

    public function getMessageTemplate(): string
    {
        return $this->messageTemplate;
    }

    public function setMessageTemplate(string $messageTemplate): void
    {
        $this->messageTemplate = $messageTemplate;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function isAutomatedEmailEnabled(): bool
    {
        return $this->automatedEmailEnabled;
    }

    public function setAutomatedEmailEnabled(bool $automatedEmailEnabled): void
    {
        $this->automatedEmailEnabled = $automatedEmailEnabled;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
