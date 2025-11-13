<?php

use Arcanum\Sdk\Model\ArcanumToken;
use Arcanum\Sdk\Model\ArcanumUser;

test('can create token', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $token = new ArcanumToken(
        'token-principal',
        'api-key-123',
        'api-secret-456',
        true,
        $owner,
        time() + 3600,
        ['ROLE_TOKEN']
    );

    expect($token)->toBeInstanceOf(ArcanumToken::class)
        ->and($token->principal)->toBe('token-principal')
        ->and($token->apiKey)->toBe('api-key-123')
        ->and($token->apiSecret)->toBe('api-secret-456')
        ->and($token->userToken)->toBeTrue()
        ->and($token->owner)->toBeInstanceOf(ArcanumUser::class)
        ->and($token->authorities)->toHaveCount(1);
});

test('isExpired returns false for valid token', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $token = new ArcanumToken(
        'token-principal',
        'api-key-123',
        null,
        true,
        $owner,
        time() + 3600,
        ['ROLE_TOKEN']
    );

    expect($token->isExpired())->toBeFalse();
});

test('isExpired returns true for expired token', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $token = new ArcanumToken(
        'token-principal',
        'api-key-123',
        null,
        true,
        $owner,
        time() - 3600,
        ['ROLE_TOKEN']
    );

    expect($token->isExpired())->toBeTrue();
});

test('getExpiryDate returns correct DateTime', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $expiryTimestamp = time() + 3600;
    $token = new ArcanumToken(
        'token-principal',
        'api-key-123',
        null,
        true,
        $owner,
        $expiryTimestamp,
        ['ROLE_TOKEN']
    );

    $expiryDate = $token->getExpiryDate();

    expect($expiryDate)->toBeInstanceOf(DateTime::class)
        ->and($expiryDate->getTimestamp())->toBe($expiryTimestamp);
});

test('toArray returns correct structure', function () {
    $owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $token = new ArcanumToken(
        'token-principal',
        'api-key-123',
        'api-secret-456',
        true,
        $owner,
        time() + 3600,
        ['ROLE_TOKEN']
    );
    $array = $token->toArray();

    expect($array)->toBeArray()
        ->and($array['principal'])->toBe('token-principal')
        ->and($array['apiKey'])->toBe('api-key-123')
        ->and($array['apiSecret'])->toBe('api-secret-456')
        ->and($array['userToken'])->toBeTrue()
        ->and($array['owner'])->toBeArray()
        ->and($array['authorities'])->toHaveCount(1);
});
