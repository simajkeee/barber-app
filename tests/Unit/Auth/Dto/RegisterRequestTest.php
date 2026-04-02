<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\Dto;

use App\Auth\Dto\RegisterRequest;
use App\Auth\Enum\UserLocale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(RegisterRequest::class)]
final class RegisterRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    private function validDto(array $overrides = []): RegisterRequest
    {
        return new RegisterRequest(
            email: $overrides['email'] ?? 'test@example.com',
            password: $overrides['password'] ?? 'password123',
            confirmPassword: $overrides['confirmPassword'] ?? 'password123',
            firstName: $overrides['firstName'] ?? 'John',
            lastName: $overrides['lastName'] ?? 'Doe',
            locale: $overrides['locale'] ?? UserLocale::VI,
            phoneNumber: $overrides['phoneNumber'] ?? '0901234567',
        );
    }

    #[Test]
    public function validDtoPassesValidation(): void
    {
        $violations = $this->validator->validate($this->validDto());

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function matchingConfirmPasswordPassesValidation(): void
    {
        $dto = $this->validDto(['password' => 'mySecret99', 'confirmPassword' => 'mySecret99']);
        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    #[Test]
    public function mismatchedConfirmPasswordFailsValidation(): void
    {
        $dto = $this->validDto(['password' => 'password123', 'confirmPassword' => 'different99']);
        $violations = $this->validator->validate($dto);

        $this->assertCount(1, $violations);
        $this->assertSame('confirmPassword', $violations[0]->getPropertyPath());
        $this->assertSame('Passwords do not match.', $violations[0]->getMessage());
    }

    #[Test]
    public function emptyConfirmPasswordFailsNotBlank(): void
    {
        $dto = $this->validDto(['confirmPassword' => '']);
        $violations = $this->validator->validate($dto);

        $paths = array_map(fn ($v) => $v->getPropertyPath(), iterator_to_array($violations));
        $this->assertContains('confirmPassword', $paths);
    }

    #[Test]
    public function blankPasswordAlsoInvalidatesConfirmPassword(): void
    {
        $dto = $this->validDto(['password' => '', 'confirmPassword' => '']);
        $violations = $this->validator->validate($dto);

        // password fails NotBlank; confirmPassword fails NotBlank (not EqualTo, since both are empty)
        $paths = array_map(fn ($v) => $v->getPropertyPath(), iterator_to_array($violations));
        $this->assertContains('password', $paths);
        $this->assertContains('confirmPassword', $paths);
    }
}
