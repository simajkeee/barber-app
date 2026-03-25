<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Client\Dto\ClientListFilter;
use App\Client\Dto\CreateClientRequest;
use App\Client\Dto\UpdateClientRequest;
use App\Client\Util\PhoneNormalizer;
use App\Common\Exception\ApiException;
use App\Entity\Client;
use App\Entity\Shop;
use App\Repository\ClientRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class ClientService
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return array{data: array<array<string, mixed>>, pagination: array{nextCursor: ?string, hasMore: bool}}
     */
    public function list(Shop $shop, ClientListFilter $filter): array
    {
        $result = $this->clientRepository->findByShopWithFilter($shop, $filter);

        return [
            'data' => array_map(self::serializeClient(...), $result['clients']),
            'pagination' => [
                'nextCursor' => $result['nextCursor'],
                'hasMore' => $result['hasMore'],
            ],
        ];
    }

    public function create(Shop $shop, CreateClientRequest $dto): Client
    {
        $normalizedPhone = PhoneNormalizer::normalize($dto->phone);
        if (!PhoneNormalizer::isValid($normalizedPhone)) {
            throw new ApiException('VALIDATION_ERROR', 'Validation failed.', 400, [['field' => 'phone', 'message' => 'Phone number format is invalid.']]);
        }

        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName(trim($dto->firstName));
        $client->setLastName(trim($dto->lastName));
        $client->setPhone($normalizedPhone);
        $client->setEmail($dto->email);
        $client->setNotes($dto->notes);

        $this->em->persist($client);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('PHONE_ALREADY_EXISTS', 'A client with this phone number already exists in your shop.', 409);
        }

        return $client;
    }

    public function get(Shop $shop, Uuid $id): Client
    {
        $client = $this->clientRepository->findByShopAndId($shop, $id);
        if (null === $client) {
            throw new ApiException('CLIENT_NOT_FOUND', 'Client not found.', 404);
        }

        return $client;
    }

    /**
     * @param string[] $providedFields JSON keys present in the request body
     */
    public function update(Shop $shop, Uuid $id, UpdateClientRequest $dto, array $providedFields): Client
    {
        $client = $this->get($shop, $id);

        if (null !== $dto->firstName) {
            $client->setFirstName(trim($dto->firstName));
        }
        if (null !== $dto->lastName) {
            $client->setLastName(trim($dto->lastName));
        }
        if (null !== $dto->phone) {
            $normalizedPhone = PhoneNormalizer::normalize($dto->phone);
            if (!PhoneNormalizer::isValid($normalizedPhone)) {
                throw new ApiException('VALIDATION_ERROR', 'Validation failed.', 400, [['field' => 'phone', 'message' => 'Phone number format is invalid.']]);
            }

            $existing = $this->clientRepository->findByShopAndPhone($shop, $normalizedPhone);
            if (null !== $existing && $existing->getId()->toRfc4122() !== $client->getId()->toRfc4122()) {
                throw new ApiException('PHONE_ALREADY_EXISTS', 'A client with this phone number already exists in your shop.', 409);
            }

            $client->setPhone($normalizedPhone);
        }
        if (\in_array('email', $providedFields, true)) {
            $client->setEmail($dto->email);
        }
        if (\in_array('notes', $providedFields, true)) {
            $client->setNotes($dto->notes);
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('PHONE_ALREADY_EXISTS', 'A client with this phone number already exists in your shop.', 409);
        }

        return $client;
    }

    public function delete(Shop $shop, Uuid $id): void
    {
        $client = $this->get($shop, $id);
        $this->em->remove($client);
        $this->em->flush();
    }

    public function recordVisit(Client $client, \DateTimeImmutable $visitDate): void
    {
        $client->setVisitCount($client->getVisitCount() + 1);

        if (null === $client->getLastVisitAt() || $visitDate > $client->getLastVisitAt()) {
            $client->setLastVisitAt($visitDate);
        }

        $this->em->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeClient(Client $client): array
    {
        return [
            'id' => (string) $client->getId(),
            'firstName' => $client->getFirstName(),
            'lastName' => $client->getLastName(),
            'phone' => $client->getPhone(),
            'email' => $client->getEmail(),
            'notes' => $client->getNotes(),
            'lastVisitAt' => $client->getLastVisitAt()?->format(\DateTimeInterface::ATOM),
            'visitCount' => $client->getVisitCount(),
            'createdAt' => $client->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $client->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
