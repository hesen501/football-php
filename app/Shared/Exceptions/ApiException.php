<?php

namespace App\Shared\Exceptions;

use App\Shared\Http\Responses\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base class for domain/business-rule errors that need to reach the client
 * as a specific HTTP status + machine-readable error code, distinguishable
 * from validation (422), auth (401/403) and not-found (404) errors.
 *
 * Laravel calls ->render() on any exception that defines it, so subclasses
 * of this need no registration in bootstrap/app.php — they self-render.
 */
abstract class ApiException extends Exception
{
    /** @param array<string, array<int, string>> $errors */
    public function __construct(
        string $message,
        protected readonly int $httpStatus = 400,
        protected readonly ?string $errorCode = null,
        protected readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error(
            message: $this->getMessage(),
            status: $this->httpStatus,
            errorCode: $this->errorCode,
            errors: $this->errors === [] ? null : $this->errors,
        );
    }
}
