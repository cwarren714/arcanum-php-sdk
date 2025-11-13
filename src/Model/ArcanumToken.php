<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

class ArcanumToken
{
    public function __construct(
        public string $principal,
        public ?string $apiKey,
        public ?string $apiSecret,
        public bool $userToken,
        public ArcanumUser $owner,
        public int $expiry,
        public array $authorities
    ) {}

    public function isExpired(): bool
    {
        return time() > $this->expiry;
    }

    public function getExpiryDate(): \DateTime
    {
        return (new \DateTime())->setTimestamp($this->expiry);
    }

    public function toArray(): array
    {
        return [
            'principal' => $this->principal,
            'apiKey' => $this->apiKey,
            'apiSecret' => $this->apiSecret,
            'userToken' => $this->userToken,
            'owner' => [
                'id' => $this->owner->id,
                'netId' => $this->owner->netId,
                'name' => $this->owner->name,
                'authorities' => $this->owner->authorities,
            ],
            'expiry' => $this->expiry,
            'authorities' => $this->authorities,
        ];
    }
}
