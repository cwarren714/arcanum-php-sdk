<?php

use Arcanum\Sdk\Model\ArcanumVault;

test('can create vault', function () {
    $vault = new ArcanumVault(1, 'Test Vault', 'A test vault description');

    expect($vault)->toBeInstanceOf(ArcanumVault::class)
        ->and($vault->id)->toBe(1)
        ->and($vault->name)->toBe('Test Vault')
        ->and($vault->description)->toBe('A test vault description');
});

test('toArray returns correct structure', function () {
    $vault = new ArcanumVault(1, 'Test Vault', 'A test vault description');
    $array = $vault->toArray();

    expect($array)->toBeArray()
        ->and($array['id'])->toBe(1)
        ->and($array['name'])->toBe('Test Vault')
        ->and($array['description'])->toBe('A test vault description');
});
