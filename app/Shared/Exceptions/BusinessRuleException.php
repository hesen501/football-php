<?php

namespace App\Shared\Exceptions;

/**
 * A business rule was violated by an otherwise well-formed, authorized
 * request (e.g. the requested booking slot is no longer available).
 * Defaults to 409 Conflict — distinct from validation (422) errors, which
 * mean the request itself was malformed.
 */
class BusinessRuleException extends ApiException
{
    /** @param array<string, array<int, string>> $errors */
    public function __construct(string $message, string $errorCode, int $httpStatus = 409, array $errors = [])
    {
        parent::__construct($message, $httpStatus, $errorCode, $errors);
    }
}
