<?php

declare(strict_types=1);

namespace App\Common\EventListener;

use App\Common\Exception\ApiException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof ApiException) {
            $payload = [
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ];
            if ($exception->details !== []) {
                $payload['details'] = $exception->details;
            }
            $event->setResponse(new JsonResponse($payload, $exception->statusCode));
            return;
        }

        if ($exception instanceof HttpException && $exception->getPrevious() instanceof ValidationFailedException) {
            /** @var ValidationFailedException $validationException */
            $validationException = $exception->getPrevious();
            $errors = [];
            foreach ($validationException->getViolations() as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ];
            }
            $event->setResponse(new JsonResponse([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed.',
                'details' => $errors,
            ], 400));
            return;
        }

        if ($exception instanceof RateLimitExceededException) {
            $response = new JsonResponse([
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests.',
            ], 429);
            $retryAfterSeconds = max(0, $exception->getRetryAfter()->getTimestamp() - time());
            $response->headers->set('Retry-After', (string) $retryAfterSeconds);
            $event->setResponse($response);
            return;
        }

        if ($exception instanceof HttpException) {
            $event->setResponse(new JsonResponse([
                'code' => 'HTTP_' . $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode()));
        }
    }
}