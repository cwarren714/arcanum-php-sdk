<?php

use Arcanum\Sdk\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function createMockClient(array $responses = []): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

    return new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);
}

test('grantVaultAccess validates empty vault name', function () {
    $client = createMockClient();

    expect(fn() => $client->grantVaultAccess('', 'testuser'))
        ->toThrow(InvalidArgumentException::class, 'Vault name cannot be empty');
});

test('grantVaultAccess validates empty netId', function () {
    $client = createMockClient();

    expect(fn() => $client->grantVaultAccess('test-vault', ''))
        ->toThrow(InvalidArgumentException::class, 'NetID cannot be empty');
});

test('getProject validates empty slug', function () {
    $client = createMockClient();

    expect(fn() => $client->getProject(''))
        ->toThrow(InvalidArgumentException::class, 'Project slug cannot be empty');
});

test('getProject validates invalid slug format', function () {
    $client = createMockClient();

    expect(fn() => $client->getProject('Invalid Slug!'))
        ->toThrow(InvalidArgumentException::class, 'Project slug must be a valid slug');
});

test('grantProjectAccess validates invalid role', function () {
    $client = createMockClient();

    expect(fn() => $client->grantProjectAccess('test-project', 'testuser', 'invalid-role'))
        ->toThrow(InvalidArgumentException::class, 'Role must be one of');
});

test('grantSecretAccess validates empty secret id', function () {
    $client = createMockClient();

    expect(fn() => $client->grantSecretAccess('', 'testuser', 'viewer'))
        ->toThrow(InvalidArgumentException::class, 'Secret ID cannot be empty');
});

test('grantSecretAccess validates invalid role', function () {
    $client = createMockClient();

    expect(fn() => $client->grantSecretAccess('123', 'testuser', 'invalid-role'))
        ->toThrow(InvalidArgumentException::class, 'Role must be one of');
});

test('createVault validates missing name', function () {
    $client = createMockClient();

    expect(fn() => $client->createVault([]))
        ->toThrow(InvalidArgumentException::class, 'Vault name is required');
});

test('createProject validates missing name', function () {
    $client = createMockClient();

    expect(fn() => $client->createProject([]))
        ->toThrow(InvalidArgumentException::class, 'Project name is required');
});

test('createToken validates missing principal', function () {
    $client = createMockClient();

    expect(fn() => $client->createToken([]))
        ->toThrow(InvalidArgumentException::class, 'Token principal is required');
});

test('revokeToken validates missing principal', function () {
    $client = createMockClient();

    expect(fn() => $client->revokeToken([]))
        ->toThrow(InvalidArgumentException::class, 'Token principal is required');
});

test('grantUserAuthority validates missing authority', function () {
    $client = createMockClient();

    expect(fn() => $client->grantUserAuthority('testuser', []))
        ->toThrow(InvalidArgumentException::class, 'Authority is required');
});

test('revokeUserAuthority validates missing authority', function () {
    $client = createMockClient();

    expect(fn() => $client->revokeUserAuthority('testuser', []))
        ->toThrow(InvalidArgumentException::class, 'Authority is required');
});

test('client constructor validates empty api key', function () {
    expect(fn() => new Client('', 'secret'))
        ->toThrow(InvalidArgumentException::class, 'API Key and Secret are required');
});

test('client constructor validates empty api secret', function () {
    expect(fn() => new Client('key', ''))
        ->toThrow(InvalidArgumentException::class, 'API Key and Secret are required');
});
