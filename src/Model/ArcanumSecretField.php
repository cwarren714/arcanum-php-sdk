<?php

declare(strict_types=1);

namespace Arcanum\Sdk\Model;

/**
 * Represents a single field within a decrypted Arcanum secret.
 */
class ArcanumSecretField
{
    /**
     * @param string $name The display name of the secret field.
     * @param string $slug The slug (key) of the secret field.
     * @param string $value The decrypted value of the secret field.
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $value
    ){}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'value' => $this->value,
        ];
    }
}
