<?php

use Arcanum\Sdk\Model\ArcanumSelfInfo;
use Arcanum\Sdk\Model\ArcanumUser;

test('can create self info', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $selfInfo = new ArcanumSelfInfo(
        'self-principal',
        'encrypted-secret-data',
        true,
        $owner,
        time() + 3600,
        ['ROLE_USER', 'ROLE_ADMIN']
    );

    expect($selfInfo)->toBeInstanceOf(ArcanumSelfInfo::class)
        ->and($selfInfo->principal)->toBe('self-principal')
        ->and($selfInfo->encryptedSecret)->toBe('encrypted-secret-data')
        ->and($selfInfo->userToken)->toBeTrue()
        ->and($selfInfo->owner)->toBeInstanceOf(ArcanumUser::class)
        ->and($selfInfo->authorities)->toHaveCount(2);
});

test('isExpired returns false for valid token', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $selfInfo = new ArcanumSelfInfo(
        'self-principal',
        'encrypted-secret-data',
        true,
        $owner,
        time() + 3600,
        ['ROLE_USER']
    );

    expect($selfInfo->isExpired())->toBeFalse();
});

test('isExpired returns true for expired token', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $selfInfo = new ArcanumSelfInfo(
        'self-principal',
        'encrypted-secret-data',
        true,
        $owner,
        time() - 3600,
        ['ROLE_USER']
    );

    expect($selfInfo->isExpired())->toBeTrue();
});

test('getExpiryDate returns correct DateTime', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $expiryTimestamp = time() + 3600;
    $selfInfo = new ArcanumSelfInfo(
        'self-principal',
        'encrypted-secret-data',
        true,
        $owner,
        $expiryTimestamp,
        ['ROLE_USER']
    );

    $expiryDate = $selfInfo->getExpiryDate();

    expect($expiryDate)->toBeInstanceOf(DateTime::class)
        ->and($expiryDate->getTimestamp())->toBe($expiryTimestamp);
});

test('hasAuthority returns true for existing authority', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $selfInfo = new ArcanumSelfInfo(
        'self-principal',
        'encrypted-secret-data',
        true,
        $owner,
        time() + 3600,
        ['ROLE_USER', 'ROLE_ADMIN']
    );

    expect($selfInfo->hasAuthority('ROLE_USER'))->toBeTrue()
        ->and($selfInfo->hasAuthority('ROLE_ADMIN'))->toBeTrue();
});

test('hasAuthority returns false for non-existent authority', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $selfInfo = new ArcanumSelfInfo(
        'self-principal',
        'encrypted-secret-data',
        true,
        $owner,
        time() + 3600,
        ['ROLE_USER']
    );

    expect($selfInfo->hasAuthority('ROLE_ADMIN'))->toBeFalse();
});

test('toArray returns correct structure', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $selfInfo = new ArcanumSelfInfo(
        'self-principal',
        'encrypted-secret-data',
        true,
        $owner,
        time() + 3600,
        ['ROLE_USER', 'ROLE_ADMIN']
    );
    $array = $selfInfo->toArray();

    expect($array)->toBeArray()
        ->and($array['principal'])->toBe('self-principal')
        ->and($array['encryptedSecret'])->toBe('encrypted-secret-data')
        ->and($array['userToken'])->toBeTrue()
        ->and($array['owner'])->toBeArray()
        ->and($array['authorities'])->toHaveCount(2);
});
