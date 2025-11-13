<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

/**
 * Represents metadata about an encrypted secret stored in Arcanum.
 */
class ArcanumEncryptedSecret
{
    /**
     * @param int $id The unique ID of the secret.
     * @param string $name The user-defined name of the secret.
     * @param string $azureId The identifier for the secret in the underlying store (e.g., Azure Key Vault ID).
     * @param array $fields List of field slugs available within this secret.
     * @param ArcanumVault $vault The vault this secret belongs to.
     * @param ArcanumUser $owner The owner of the secret.
     */
    public function __construct(
        public int          $id,
        public string       $name,
        public string       $azureId,
        public array        $fields,
        public ArcanumVault $vault,
        public ArcanumUser  $owner
    ) {}

    public function hasFieldSlug(string $slug): bool
    {
        return in_array($slug, $this->fields, true);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'azureId' => $this->azureId,
            'fields' => $this->fields,
            'vault' => $this->vault->toArray(),
            'owner' => $this->owner->toArray(),
        ];
    }
}
