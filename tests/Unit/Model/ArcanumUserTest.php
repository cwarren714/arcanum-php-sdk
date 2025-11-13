<?php

use Arcanum\Sdk\Model\ArcanumUser;

test('can create user', function () {
    $user = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER', 'ROLE_ADMIN']);

    expect($user)->toBeInstanceOf(ArcanumUser::class)
        ->and($user->id)->toBe(1)
        ->and($user->netId)->toBe('testuser')
        ->and($user->name)->toBe('Test User')
        ->and($user->authorities)->toHaveCount(2);
});

test('hasAuthority returns true for existing authority', function () {
    $user = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER', 'ROLE_ADMIN']);

    expect($user->hasAuthority('ROLE_USER'))->toBeTrue()
        ->and($user->hasAuthority('ROLE_ADMIN'))->toBeTrue();
});

test('hasAuthority returns false for non-existent authority', function () {
    $user = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);

    expect($user->hasAuthority('ROLE_ADMIN'))->toBeFalse()
        ->and($user->hasAuthority('ROLE_SUPERADMIN'))->toBeFalse();
});

test('toArray returns correct structure', function () {
    $user = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER', 'ROLE_ADMIN']);
    $array = $user->toArray();

    expect($array)->toBeArray()
        ->and($array['id'])->toBe(1)
        ->and($array['netId'])->toBe('testuser')
        ->and($array['name'])->toBe('Test User')
        ->and($array['authorities'])->toHaveCount(2)
        ->and($array['authorities'])->toContain('ROLE_USER')
        ->and($array['authorities'])->toContain('ROLE_ADMIN');
});
