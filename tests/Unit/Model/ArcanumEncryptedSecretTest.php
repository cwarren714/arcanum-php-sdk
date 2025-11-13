<?php

use Arcanum\Sdk\Model\ArcanumEncryptedSecret;
use Arcanum\Sdk\Model\ArcanumVault;
use Arcanum\Sdk\Model\ArcanumUser;

test('can create encrypted secret', function () {
    $vault = new ArcanumVault(1, 'Test Vault', 'Test vault description');
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);

    $secret = new ArcanumEncryptedSecret(
        100,
        'Test Secret',
        'azure-secret-id-123',
        ['api-key', 'api-secret', 'db-password'],
        $vault,
        $owner
    );

    expect($secret)->toBeInstanceOf(ArcanumEncryptedSecret::class)
        ->and($secret->id)->toBe(100)
        ->and($secret->name)->toBe('Test Secret')
        ->and($secret->azureId)->toBe('azure-secret-id-123')
        ->and($secret->fields)->toHaveCount(3)
        ->and($secret->vault)->toBeInstanceOf(ArcanumVault::class)
        ->and($secret->owner)->toBeInstanceOf(ArcanumUser::class);
});

test('hasFieldSlug returns true for existing field slugs', function () {
    $vault = new ArcanumVault(1, 'Test Vault', 'Test vault description');
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $secret = new ArcanumEncryptedSecret(
        100,
        'Test Secret',
        'azure-secret-id-123',
        ['api-key', 'api-secret', 'db-password'],
        $vault,
        $owner
    );

    expect($secret->hasFieldSlug('api-key'))->toBeTrue()
        ->and($secret->hasFieldSlug('api-secret'))->toBeTrue()
        ->and($secret->hasFieldSlug('db-password'))->toBeTrue();
});

test('hasFieldSlug returns false for non-existent field slugs', function () {
    $vault = new ArcanumVault(1, 'Test Vault', 'Test vault description');
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $secret = new ArcanumEncryptedSecret(
        100,
        'Test Secret',
        'azure-secret-id-123',
        ['api-key', 'api-secret'],
        $vault,
        $owner
    );

    expect($secret->hasFieldSlug('non-existent'))->toBeFalse();
});

test('toArray returns correct structure', function () {
    $vault = new ArcanumVault(1, 'Test Vault', 'Test vault description');
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $secret = new ArcanumEncryptedSecret(
        100,
        'Test Secret',
        'azure-secret-id-123',
        ['api-key', 'api-secret'],
        $vault,
        $owner
    );
    $array = $secret->toArray();

    expect($array)->toBeArray()
        ->and($array['id'])->toBe(100)
        ->and($array['name'])->toBe('Test Secret')
        ->and($array['azureId'])->toBe('azure-secret-id-123')
        ->and($array['fields'])->toHaveCount(2)
        ->and($array['vault'])->toBeArray()
        ->and($array['owner'])->toBeArray();
});
