<?php

declare(strict_types=1);

namespace App\Shop\Service;

use App\Entity\Shop;
use App\Entity\ShopService;
use App\Repository\ShopServiceRepository;
use App\Shop\Dto\CreateServiceRequest;
use App\Shop\Dto\UpdateServiceRequest;
use Doctrine\ORM\EntityManagerInterface;

final class ShopServiceManager
{
    public function __construct(
        private readonly ShopServiceRepository $shopServiceRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function createService(Shop $shop, CreateServiceRequest $dto): ShopService
    {
        $service = new ShopService();
        $service->setShop($shop);
        $service->setName($dto->name);
        $service->setDurationMinutes($dto->durationMinutes);
        $service->setPrice($dto->price);
        $service->setSortOrder($dto->sortOrder);

        $this->em->persist($service);
        $this->em->flush();

        return $service;
    }

    public function updateService(ShopService $service, UpdateServiceRequest $dto): ShopService
    {
        if (null !== $dto->name) {
            $service->setName($dto->name);
        }
        if (null !== $dto->durationMinutes) {
            $service->setDurationMinutes($dto->durationMinutes);
        }
        if (null !== $dto->price) {
            $service->setPrice($dto->price);
        }
        if (null !== $dto->isActive) {
            $service->setIsActive($dto->isActive);
        }
        if (null !== $dto->sortOrder) {
            $service->setSortOrder($dto->sortOrder);
        }

        $this->em->flush();

        return $service;
    }

    public function deleteService(ShopService $service): void
    {
        $service->setIsActive(false);
        $this->em->flush();
    }

    /**
     * @return ShopService[]
     */
    public function listServices(Shop $shop, bool $includeInactive = false): array
    {
        return $this->shopServiceRepository->findByShop($shop, $includeInactive);
    }

    /**
     * @return array<string, mixed>
     */
    public static function serializeService(ShopService $service): array
    {
        return [
            'id' => (string) $service->getId(),
            'name' => $service->getName(),
            'durationMinutes' => $service->getDurationMinutes(),
            'price' => $service->getPrice(),
            'isActive' => $service->isActive(),
            'sortOrder' => $service->getSortOrder(),
            'createdAt' => $service->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $service->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
