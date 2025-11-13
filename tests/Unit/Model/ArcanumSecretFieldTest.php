<?php

use Arcanum\Sdk\Model\ArcanumSecretField;

test('can create secret field', function () {
    $field = new ArcanumSecretField('API Key', 'api-key', 'secret-value-123');

    expect($field)->toBeInstanceOf(ArcanumSecretField::class)
        ->and($field->name)->toBe('API Key')
        ->and($field->slug)->toBe('api-key')
        ->and($field->value)->toBe('secret-value-123');
});

test('toArray returns correct structure', function () {
    $field = new ArcanumSecretField('API Key', 'api-key', 'secret-value-123');
    $array = $field->toArray();

    expect($array)->toBeArray()
        ->and($array['name'])->toBe('API Key')
        ->and($array['slug'])->toBe('api-key')
        ->and($array['value'])->toBe('secret-value-123');
});
