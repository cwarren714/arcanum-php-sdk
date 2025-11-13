<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

/**
 * Represents information about the currently authenticated principal (User or Token).
 */
class ArcanumSelfInfo
{
    /**
     * @param string $principal The principal identifier (e.g., NetID or Token Principal).
     * @param string $encryptedSecret The encrypted secret associated with the principal (if applicable).
     * @param bool $userToken Indicates if the principal represents a user token.
     * @param ArcanumUser $owner The owner of the token or the user themselves.
     * @param int $expiry The expiry timestamp (Unix epoch) for the token, if applicable.
     * @param string[] $authorities List of authorities/roles granted to the principal.
     */
    public function __construct(
        public string $principal,
        public string $encryptedSecret,
        public bool $userToken,
        public ArcanumUser $owner,
        public int $expiry,
        public array $authorities
    ){}

    public function isExpired(): bool
    {
        return time() > $this->expiry;
    }

    public function getExpiryDate(): \DateTime
    {
        return (new \DateTime())->setTimestamp($this->expiry);
    }

    public function hasAuthority(string $authority): bool
    {
        return in_array($authority, $this->authorities, true);
    }

    public function toArray(): array
    {
        return [
            'principal' => $this->principal,
            'encryptedSecret' => $this->encryptedSecret,
            'userToken' => $this->userToken,
            'owner' => $this->owner->toArray(),
            'expiry' => $this->expiry,
            'authorities' => $this->authorities,
        ];
    }
}
