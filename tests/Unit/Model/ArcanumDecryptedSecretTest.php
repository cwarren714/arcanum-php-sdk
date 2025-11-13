<?php

use Arcanum\Sdk\Model\ArcanumDecryptedSecret;
use Arcanum\Sdk\Model\ArcanumSecretField;

beforeEach(function () {
    $this->fields = [
        new ArcanumSecretField('API Key', 'api-key', 'secret-api-key-123'),
        new ArcanumSecretField('API Secret', 'api-secret', 'secret-api-secret-456'),
        new ArcanumSecretField('Database Password', 'db-password', 'super-secret-pass'),
    ];

    $this->secret = new ArcanumDecryptedSecret(
        'Test Secret',
        'test-secret',
        'A test secret description',
        'test-vault',
        $this->fields
    );
});

test('can create decrypted secret', function () {
    expect($this->secret)->toBeInstanceOf(ArcanumDecryptedSecret::class)
        ->and($this->secret->name)->toBe('Test Secret')
        ->and($this->secret->slug)->toBe('test-secret')
        ->and($this->secret->description)->toBe('A test secret description')
        ->and($this->secret->vault)->toBe('test-vault')
        ->and($this->secret->fields)->toHaveCount(3);
});

test('getFieldValue returns correct value', function () {
    expect($this->secret->getFieldValue('api-key'))->toBe('secret-api-key-123')
        ->and($this->secret->getFieldValue('api-secret'))->toBe('secret-api-secret-456')
        ->and($this->secret->getFieldValue('db-password'))->toBe('super-secret-pass');
});

test('getFieldValue returns null for non-existent field', function () {
    expect($this->secret->getFieldValue('non-existent'))->toBeNull();
});

test('hasField returns true for existing fields', function () {
    expect($this->secret->hasField('api-key'))->toBeTrue()
        ->and($this->secret->hasField('api-secret'))->toBeTrue()
        ->and($this->secret->hasField('db-password'))->toBeTrue();
});

test('hasField returns false for non-existent fields', function () {
    expect($this->secret->hasField('non-existent'))->toBeFalse();
});

test('getField returns correct field object', function () {
    $field = $this->secret->getField('api-key');

    expect($field)->toBeInstanceOf(ArcanumSecretField::class)
        ->and($field->name)->toBe('API Key')
        ->and($field->slug)->toBe('api-key')
        ->and($field->value)->toBe('secret-api-key-123');
});

test('getField returns null for non-existent field', function () {
    expect($this->secret->getField('non-existent'))->toBeNull();
});

test('toArray returns correct structure', function () {
    $array = $this->secret->toArray();

    expect($array)->toBeArray()
        ->and($array['name'])->toBe('Test Secret')
        ->and($array['slug'])->toBe('test-secret')
        ->and($array['description'])->toBe('A test secret description')
        ->and($array['vault'])->toBe('test-vault')
        ->and($array['fields'])->toHaveCount(3)
        ->and($array['fields'][0])->toHaveKey('name')
        ->and($array['fields'][0])->toHaveKey('slug')
        ->and($array['fields'][0])->toHaveKey('value');
});
