<?php

declare(strict_types=1);

namespace App\PublicBooking\Service;

use App\Client\Util\PhoneNormalizer;
use App\Entity\Client;
use App\Entity\Shop;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ClientResolverService
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function resolveClient(Shop $shop, string $name, string $phone): Client
    {
        $normalizedPhone = PhoneNormalizer::normalize($phone);

        $client = $this->clientRepository->findByShopAndPhone($shop, $normalizedPhone);

        if (null !== $client) {
            $nameParts = self::splitName($name);
            if ($client->getFirstName() !== $nameParts['firstName'] || $client->getLastName() !== $nameParts['lastName']) {
                $client->setFirstName($nameParts['firstName']);
                $client->setLastName($nameParts['lastName']);
            }

            return $client;
        }

        $nameParts = self::splitName($name);
        $client = new Client();
        $client->setShop($shop);
        $client->setFirstName($nameParts['firstName']);
        $client->setLastName($nameParts['lastName']);
        $client->setPhone($normalizedPhone);

        $this->em->persist($client);

        return $client;
    }

    /**
     * @return array{firstName: string, lastName: string}
     */
    private static function splitName(string $fullName): array
    {
        $name = trim(strip_tags($fullName));
        $parts = preg_split('/\s+/', $name, 2);

        return [
            'firstName' => $parts[0] ?? $name,
            'lastName' => $parts[1] ?? '',
        ];
    }
}
