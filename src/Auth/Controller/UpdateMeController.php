<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\UpdateProfileRequest;
use App\Auth\Service\AuthService;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[AsController]
final readonly class UpdateMeController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/me', methods: ['PUT'])]
    public function __invoke(
        #[CurrentUser] User $user,
        #[MapRequestPayload] UpdateProfileRequest $dto,
    ): JsonResponse {
        if (null !== $dto->firstName) {
            $user->setFirstName($dto->firstName);
        }
        if (null !== $dto->lastName) {
            $user->setLastName($dto->lastName);
        }
        if (null !== $dto->locale) {
            $user->setLocale($dto->locale);
        }

        $this->em->flush();

        return new JsonResponse(AuthService::serializeUser($user));
    }
}
