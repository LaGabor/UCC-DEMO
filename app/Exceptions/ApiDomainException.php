<?php

namespace App\Exceptions;

use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use Exception;

class ApiDomainException extends Exception
{
    /**
     * @param array<string, mixed>|null $errors
     */
    public function __construct(
        public readonly ApiDomainErrorCode $errorCode,
        string $message,
        public readonly ?array $errors = null,
        public readonly ApiDomainStatus $status = ApiDomainStatus::UNPROCESSABLE_ENTITY
    ) {
        parent::__construct($message);
    }
}
