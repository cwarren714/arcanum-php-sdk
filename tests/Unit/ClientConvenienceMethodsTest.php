<?php

use Arcanum\Sdk\Client;
use Arcanum\Sdk\Model\ArcanumEncryptedSecret;
use Arcanum\Sdk\Model\ArcanumProject;
use Arcanum\Sdk\Model\ArcanumVault;
use Arcanum\Sdk\Model\ArcanumDecryptedSecret;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function createMockClientWithSecrets(): Client
{
    $secretsResponse = json_encode([
        [
            'id' => 100,
            'name' => 'Database Credentials',
            'azureId' => 'azure-id-1',
            'fields' => ['username', 'password'],
            'vault' => ['id' => 1, 'name' => 'Production', 'description' => 'Prod vault'],
            'owner' => ['id' => 1, 'netId' => 'testuser', 'name' => 'Test User', 'authorities' => ['ROLE_USER']],
        ],
        [
            'id' => 200,
            'name' => 'API Keys',
            'azureId' => 'azure-id-2',
            'fields' => ['api-key', 'api-secret'],
            'vault' => ['id' => 1, 'name' => 'Production', 'description' => 'Prod vault'],
            'owner' => ['id' => 1, 'netId' => 'testuser', 'name' => 'Test User', 'authorities' => ['ROLE_USER']],
        ],
        [
            'id' => 300,
            'name' => 'Test Credentials',
            'azureId' => 'azure-id-3',
            'fields' => ['username'],
            'vault' => ['id' => 2, 'name' => 'Development', 'description' => 'Dev vault'],
            'owner' => ['id' => 1, 'netId' => 'testuser', 'name' => 'Test User', 'authorities' => ['ROLE_USER']],
        ],
    ]);

    $mock = new MockHandler([
        new Response(200, [], $secretsResponse),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

    return new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);
}

function createMockClientWithProjects(): Client
{
    $projectsResponse = json_encode([
        [
            'id' => 1,
            'name' => 'Web Application',
            'description' => 'Main web app',
            'slug' => 'web-application',
            'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User One', 'authorities' => ['ROLE_USER']],
            'secrets' => [],
        ],
        [
            'id' => 2,
            'name' => 'Mobile App',
            'description' => 'Mobile application',
            'slug' => 'mobile-app',
            'owner' => ['id' => 2, 'netId' => 'user2', 'name' => 'User Two', 'authorities' => ['ROLE_USER']],
            'secrets' => [],
        ],
        [
            'id' => 3,
            'name' => 'API Service',
            'description' => 'Backend API',
            'slug' => 'api-service',
            'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User One', 'authorities' => ['ROLE_USER']],
            'secrets' => [],
        ],
    ]);

    $mock = new MockHandler([
        new Response(200, [], $projectsResponse),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

    return new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);
}

function createMockClientWithVaults(): Client
{
    $vaultsResponse = json_encode([
        ['id' => 1, 'name' => 'Production', 'description' => 'Production vault'],
        ['id' => 2, 'name' => 'Development', 'description' => 'Development vault'],
        ['id' => 3, 'name' => 'Staging', 'description' => 'Staging vault'],
    ]);

    $mock = new MockHandler([
        new Response(200, [], $vaultsResponse),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

    return new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);
}

test('getSecretByName returns correct secret', function () {
    $client = createMockClientWithSecrets();
    $secret = $client->getSecretByName('API Keys');

    expect($secret)->toBeInstanceOf(ArcanumEncryptedSecret::class)
        ->and($secret->id)->toBe(200)
        ->and($secret->name)->toBe('API Keys');
});

test('getSecretByName returns null for non-existent secret', function () {
    $client = createMockClientWithSecrets();
    $secret = $client->getSecretByName('Non-existent Secret');

    expect($secret)->toBeNull();
});

test('getSecretByName filters by vault name', function () {
    $client = createMockClientWithSecrets();
    $secret = $client->getSecretByName('Database Credentials', 'Production');

    expect($secret)->toBeInstanceOf(ArcanumEncryptedSecret::class)
        ->and($secret->vault->name)->toBe('Production');
});

test('getSecretByName returns null when vault does not match', function () {
    $client = createMockClientWithSecrets();
    $secret = $client->getSecretByName('Database Credentials', 'Development');

    expect($secret)->toBeNull();
});

test('getSecretsByVault returns secrets from specific vault', function () {
    $client = createMockClientWithSecrets();
    $secrets = $client->getSecretsByVault('Production');

    expect($secrets)->toHaveCount(2)
        ->and($secrets[0]->vault->name)->toBe('Production')
        ->and($secrets[1]->vault->name)->toBe('Production');
});

test('getSecretsByVault returns empty array for vault with no secrets', function () {
    $client = createMockClientWithSecrets();
    $secrets = $client->getSecretsByVault('Non-existent Vault');

    expect($secrets)->toBeArray()
        ->and($secrets)->toBeEmpty();
});

test('findSecretsByName returns matching secrets', function () {
    $client = createMockClientWithSecrets();
    $secrets = $client->findSecretsByName('Credentials');

    expect($secrets)->toHaveCount(2)
        ->and($secrets[0]->name)->toContain('Credentials')
        ->and($secrets[1]->name)->toContain('Credentials');
});

test('findSecretsByName is case insensitive', function () {
    $client = createMockClientWithSecrets();
    $secrets = $client->findSecretsByName('credentials');

    expect($secrets)->toHaveCount(2);
});

test('findSecretsByName returns empty array for no matches', function () {
    $client = createMockClientWithSecrets();
    $secrets = $client->findSecretsByName('NonExistent');

    expect($secrets)->toBeEmpty();
});

test('getProjectByName returns correct project', function () {
    $client = createMockClientWithProjects();
    $project = $client->getProjectByName('Mobile App');

    expect($project)->toBeInstanceOf(ArcanumProject::class)
        ->and($project->id)->toBe(2)
        ->and($project->name)->toBe('Mobile App');
});

test('getProjectByName returns null for non-existent project', function () {
    $client = createMockClientWithProjects();
    $project = $client->getProjectByName('Non-existent Project');

    expect($project)->toBeNull();
});

test('findProjectsByName returns matching projects', function () {
    $client = createMockClientWithProjects();
    $projects = $client->findProjectsByName('App');

    expect($projects)->toHaveCount(2);
});

test('findProjectsByName is case insensitive', function () {
    $client = createMockClientWithProjects();
    $projects = $client->findProjectsByName('app');

    expect($projects)->toHaveCount(2);
});

test('getProjectsByOwner returns correct projects', function () {
    $client = createMockClientWithProjects();
    $projects = $client->getProjectsByOwner('user1');

    expect($projects)->toHaveCount(2)
        ->and($projects[0]->owner->netId)->toBe('user1')
        ->and($projects[1]->owner->netId)->toBe('user1');
});

test('getProjectsByOwner returns empty array for no matches', function () {
    $client = createMockClientWithProjects();
    $projects = $client->getProjectsByOwner('nonexistent');

    expect($projects)->toBeEmpty();
});

test('getVaultByName returns correct vault', function () {
    $client = createMockClientWithVaults();
    $vault = $client->getVaultByName('Staging');

    expect($vault)->toBeInstanceOf(ArcanumVault::class)
        ->and($vault->id)->toBe(3)
        ->and($vault->name)->toBe('Staging');
});

test('getVaultByName returns null for non-existent vault', function () {
    $client = createMockClientWithVaults();
    $vault = $client->getVaultByName('Non-existent');

    expect($vault)->toBeNull();
});

test('getDecryptedSecretByName combines search and retrieval', function () {
    $secretsResponse = json_encode([
        [
            'id' => 100,
            'name' => 'Test Secret',
            'azureId' => 'azure-id-1',
            'fields' => ['username'],
            'vault' => ['id' => 1, 'name' => 'Production', 'description' => 'Prod vault'],
            'owner' => ['id' => 1, 'netId' => 'testuser', 'name' => 'Test User', 'authorities' => ['ROLE_USER']],
        ],
    ]);

    $secretDetailResponse = json_encode([
        'name' => 'Test Secret',
        'slug' => 'test-secret',
        'description' => 'A test secret',
        'vault' => 'Production',
        'fields' => [
            ['name' => 'Username', 'slug' => 'username', 'value' => 'admin'],
        ],
    ]);

    $mock = new MockHandler([
        new Response(200, [], $secretsResponse),
        new Response(200, [], $secretDetailResponse),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);
    $client = new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);

    $secret = $client->getDecryptedSecretByName('Test Secret');

    expect($secret)->toBeInstanceOf(ArcanumDecryptedSecret::class)
        ->and($secret->name)->toBe('Test Secret')
        ->and($secret->fields)->toHaveCount(1);
});
