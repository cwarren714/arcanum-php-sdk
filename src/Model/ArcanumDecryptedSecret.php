<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

/**
 * Represents a fully decrypted Arcanum secret with its fields and values.
 */
class ArcanumDecryptedSecret
{

    /**
     * @param string $name The user-defined name of the secret.
     * @param string $slug The unique slug of the secret.
     * @param string $description The description of the secret.
     * @param string $vault The name or identifier of the vault the secret belongs to.
     * @param ArcanumSecretField[] $fields List of decrypted fields within the secret.
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
        public string $vault,
        public array $fields
    ) {}

    public function getFieldValue(string $slug): ?string
    {
        foreach ($this->fields as $field) {
            if ($field->slug === $slug) {
                return $field->value;
            }
        }
        return null;
    }

    public function hasField(string $slug): bool
    {
        foreach ($this->fields as $field) {
            if ($field->slug === $slug) {
                return true;
            }
        }
        return false;
    }

    public function getField(string $slug): ?ArcanumSecretField
    {
        foreach ($this->fields as $field) {
            if ($field->slug === $slug) {
                return $field;
            }
        }
        return null;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'vault' => $this->vault,
            'fields' => array_map(fn($field) => $field->toArray(), $this->fields),
        ];
    }
}
