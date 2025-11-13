<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

/**
 * Represents an Arcanum Vault.
 */
class ArcanumVault
{
    /**
     * @param int $id The unique ID of the vault.
     * @param string $name The name of the vault.
     * @param string $description The description of the vault.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $description
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
