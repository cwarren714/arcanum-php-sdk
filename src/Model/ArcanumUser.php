<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

/**
 * Represents an Arcanum User.
 */
class ArcanumUser
{
    /**
     * @param int $id The unique ID of the user.
     * @param string $netId The NetID of the user.
     * @param string $name The display name of the user.
     * @param string[] $authorities List of authorities/roles granted to the user.
     */
    public function __construct(
        public int $id,
        public string $netId,
        public string $name,
        public array $authorities
    ) {}

    public function hasAuthority(string $authority): bool
    {
        return in_array($authority, $this->authorities, true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'netId' => $this->netId,
            'name' => $this->name,
            'authorities' => $this->authorities,
        ];
    }
}
