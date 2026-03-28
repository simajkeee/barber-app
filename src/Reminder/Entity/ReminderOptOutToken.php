<?php

declare(strict_types=1);

namespace App\Reminder\Entity;

use App\Entity\Client;
use App\Reminder\Repository\ReminderOptOutTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReminderOptOutTokenRepository::class)]
#[ORM\Table(name: 'reminder_opt_out_tokens')]
#[ORM\UniqueConstraint(name: 'uniq_opt_out_token', columns: ['token'])]
#[ORM\UniqueConstraint(name: 'uniq_opt_out_client', columns: ['client_id'])]
final class ReminderOptOutToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Client $client;

    #[ORM\Column(length: 64)]
    private string $token;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->token = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
