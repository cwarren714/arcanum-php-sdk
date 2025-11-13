<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

/**
 * Represents an Arcanum Project.
 */
class ArcanumProject
{
    /**
     * @param int $id The unique ID of the project.
     * @param string $name The name of the project.
     * @param string $description The description of the project.
     * @param string $slug The URL-friendly slug for the project.
     * @param ArcanumUser $owner The owner of the project.
     * @param ArcanumEncryptedSecret[] $secrets List of encrypted secrets associated with the project.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public string $slug,
        public ArcanumUser $owner,
        public array $secrets
    ){}

    public function hasSecret(int $secretId): bool
    {
        foreach ($this->secrets as $secret) {
            if ($secret->id === $secretId) {
                return true;
            }
        }
        return false;
    }

    public function getSecretById(int $id): ?ArcanumEncryptedSecret
    {
        foreach ($this->secrets as $secret) {
            if ($secret->id === $id) {
                return $secret;
            }
        }
        return null;
    }

    public function getSecretByName(string $name): ?ArcanumEncryptedSecret
    {
        foreach ($this->secrets as $secret) {
            if ($secret->name === $name) {
                return $secret;
            }
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'owner' => $this->owner->toArray(),
            'secrets' => array_map(fn($secret) => $secret->toArray(), $this->secrets),
        ];
    }
}
