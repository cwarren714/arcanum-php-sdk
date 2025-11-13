<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Exception;

class RateLimitException extends ApiException
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        public ?int $retryAfter = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
