<?php

use Arcanum\Sdk\Client;
use Arcanum\Sdk\Model\ArcanumVault;
use Arcanum\Sdk\Model\ArcanumProject;
use Arcanum\Sdk\Model\ArcanumEncryptedSecret;
use Arcanum\Sdk\Model\ArcanumDecryptedSecret;
use Arcanum\Sdk\Model\ArcanumToken;
use Arcanum\Sdk\Model\ArcanumSelfInfo;
use Arcanum\Sdk\Model\ArcanumUser;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function createTypedReturnMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

    return new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);
}

test('listVaults returns array of ArcanumVault', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            ['id' => 1, 'name' => 'Vault 1', 'description' => 'First vault'],
            ['id' => 2, 'name' => 'Vault 2', 'description' => 'Second vault'],
        ])),
    ]);

    $vaults = $client->listVaults();

    expect($vaults)->toBeArray()
        ->and($vaults)->toHaveCount(2)
        ->and($vaults[0])->toBeInstanceOf(ArcanumVault::class)
        ->and($vaults[1])->toBeInstanceOf(ArcanumVault::class)
        ->and($vaults[0]->name)->toBe('Vault 1');
});

test('createVault returns ArcanumVault', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'id' => 1,
            'name' => 'New Vault',
            'description' => 'A new vault',
        ])),
    ]);

    $vault = $client->createVault(['name' => 'New Vault', 'description' => 'A new vault']);

    expect($vault)->toBeInstanceOf(ArcanumVault::class)
        ->and($vault->id)->toBe(1)
        ->and($vault->name)->toBe('New Vault');
});

test('listProjects returns array of ArcanumProject', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            [
                'id' => 1,
                'name' => 'Project 1',
                'description' => 'First project',
                'slug' => 'project-1',
                'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
                'secrets' => [],
            ],
        ])),
    ]);

    $projects = $client->listProjects();

    expect($projects)->toBeArray()
        ->and($projects)->toHaveCount(1)
        ->and($projects[0])->toBeInstanceOf(ArcanumProject::class)
        ->and($projects[0]->name)->toBe('Project 1');
});

test('listProjects returns null for empty response', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([])),
    ]);

    $projects = $client->listProjects();

    expect($projects)->toBeNull();
});

test('createProject returns ArcanumProject', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'id' => 1,
            'name' => 'New Project',
            'description' => 'A new project',
            'slug' => 'new-project',
            'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
            'secrets' => [],
        ])),
    ]);

    $project = $client->createProject(['name' => 'New Project']);

    expect($project)->toBeInstanceOf(ArcanumProject::class)
        ->and($project->name)->toBe('New Project');
});

test('getProject returns ArcanumProject', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'id' => 1,
            'name' => 'Test Project',
            'description' => 'A test project',
            'slug' => 'test-project',
            'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
            'secrets' => [],
        ])),
    ]);

    $project = $client->getProject('test-project');

    expect($project)->toBeInstanceOf(ArcanumProject::class)
        ->and($project->slug)->toBe('test-project');
});

test('editProject returns ArcanumProject', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'id' => 1,
            'name' => 'Updated Project',
            'description' => 'Updated description',
            'slug' => 'test-project',
            'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
            'secrets' => [],
        ])),
    ]);

    $project = $client->editProject('test-project', ['name' => 'Updated Project']);

    expect($project)->toBeInstanceOf(ArcanumProject::class)
        ->and($project->name)->toBe('Updated Project');
});

test('listSecrets returns array of ArcanumEncryptedSecret', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            [
                'id' => 1,
                'name' => 'Secret 1',
                'azureId' => 'azure-id-1',
                'fields' => ['field1'],
                'vault' => ['id' => 1, 'name' => 'Vault 1', 'description' => 'First vault'],
                'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
            ],
        ])),
    ]);

    $secrets = $client->listSecrets();

    expect($secrets)->toBeArray()
        ->and($secrets)->toHaveCount(1)
        ->and($secrets[0])->toBeInstanceOf(ArcanumEncryptedSecret::class)
        ->and($secrets[0]->name)->toBe('Secret 1');
});

test('getSecret returns ArcanumDecryptedSecret', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'name' => 'Test Secret',
            'slug' => 'test-secret',
            'description' => 'A test secret',
            'vault' => 'Test Vault',
            'fields' => [
                ['name' => 'Username', 'slug' => 'username', 'value' => 'admin'],
            ],
        ])),
    ]);

    $secret = $client->getSecret('123');

    expect($secret)->toBeInstanceOf(ArcanumDecryptedSecret::class)
        ->and($secret->name)->toBe('Test Secret')
        ->and($secret->fields)->toHaveCount(1);
});

test('listTokens returns array of ArcanumToken', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            [
                'principal' => 'token-1',
                'apiKey' => 'key-1',
                'userToken' => true,
                'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
                'expiry' => time() + 3600,
                'authorities' => ['ROLE_TOKEN'],
            ],
        ])),
    ]);

    $tokens = $client->listTokens();

    expect($tokens)->toBeArray()
        ->and($tokens)->toHaveCount(1)
        ->and($tokens[0])->toBeInstanceOf(ArcanumToken::class)
        ->and($tokens[0]->principal)->toBe('token-1');
});

test('getTokenInfo returns ArcanumSelfInfo', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'principal' => 'self-principal',
            'encryptedSecret' => 'encrypted-data',
            'userToken' => true,
            'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
            'expiry' => time() + 3600,
            'authorities' => ['ROLE_USER'],
        ])),
    ]);

    $info = $client->getTokenInfo();

    expect($info)->toBeInstanceOf(ArcanumSelfInfo::class)
        ->and($info->principal)->toBe('self-principal');
});

test('createToken returns ArcanumToken', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'principal' => 'new-token',
            'apiKey' => 'new-key',
            'apiSecret' => 'new-secret',
            'userToken' => true,
            'owner' => ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
            'expiry' => time() + 3600,
            'authorities' => ['ROLE_TOKEN'],
        ])),
    ]);

    $token = $client->createToken(['principal' => 'new-token']);

    expect($token)->toBeInstanceOf(ArcanumToken::class)
        ->and($token->principal)->toBe('new-token')
        ->and($token->apiKey)->toBe('new-key')
        ->and($token->apiSecret)->toBe('new-secret');
});

test('getSelfUserInfo returns ArcanumUser', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            'id' => 1,
            'netId' => 'testuser',
            'name' => 'Test User',
            'authorities' => ['ROLE_USER', 'ROLE_ADMIN'],
        ])),
    ]);

    $user = $client->getSelfUserInfo();

    expect($user)->toBeInstanceOf(ArcanumUser::class)
        ->and($user->netId)->toBe('testuser')
        ->and($user->authorities)->toHaveCount(2);
});

test('listUsersForSharing returns array of ArcanumUser', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            ['id' => 1, 'netId' => 'user1', 'name' => 'User 1', 'authorities' => ['ROLE_USER']],
            ['id' => 2, 'netId' => 'user2', 'name' => 'User 2', 'authorities' => ['ROLE_USER']],
        ])),
    ]);

    $users = $client->listUsersForSharing();

    expect($users)->toBeArray()
        ->and($users)->toHaveCount(2)
        ->and($users[0])->toBeInstanceOf(ArcanumUser::class)
        ->and($users[1])->toBeInstanceOf(ArcanumUser::class);
});

test('listUsersForAdmin returns array of ArcanumUser', function () {
    $client = createTypedReturnMockClient([
        new Response(200, [], json_encode([
            ['id' => 1, 'netId' => 'admin1', 'name' => 'Admin 1', 'authorities' => ['ROLE_ADMIN']],
        ])),
    ]);

    $users = $client->listUsersForAdmin();

    expect($users)->toBeArray()
        ->and($users)->toHaveCount(1)
        ->and($users[0])->toBeInstanceOf(ArcanumUser::class);
});
