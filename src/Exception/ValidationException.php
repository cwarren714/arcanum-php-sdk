<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Exception;

class ValidationException extends ApiException
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        public array $errors = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
