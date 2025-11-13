<?php

use Arcanum\Sdk\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function createAccessControlMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

    return new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);
}

test('grantProjectAccess works with viewer role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantProjectAccess('test-project', 'testuser', 'viewer');

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('success');
});

test('grantProjectAccess works with editor role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantProjectAccess('test-project', 'testuser', 'editor');

    expect($result)->toBeArray();
});

test('grantProjectAccess works with admin role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantProjectAccess('test-project', 'testuser', 'admin');

    expect($result)->toBeArray();
});

test('grantProjectAccess is case insensitive for roles', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantProjectAccess('test-project', 'testuser', 'VIEWER');

    expect($result)->toBeArray();
});

test('grantProjectViewAccess calls grantProjectAccess with viewer role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantProjectViewAccess('test-project', 'testuser');

    expect($result)->toBeArray();
});

test('grantProjectEditorAccess calls grantProjectAccess with editor role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantProjectEditorAccess('test-project', 'testuser');

    expect($result)->toBeArray();
});

test('grantProjectAdminAccess calls grantProjectAccess with admin role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantProjectAdminAccess('test-project', 'testuser');

    expect($result)->toBeArray();
});

test('grantSecretAccess works with viewer role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantSecretAccess('123', 'testuser', 'viewer');

    expect($result)->toBeArray();
});

test('grantSecretAccess works with editor role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantSecretAccess('123', 'testuser', 'editor');

    expect($result)->toBeArray();
});

test('grantSecretAccess works with admin role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantSecretAccess('123', 'testuser', 'admin');

    expect($result)->toBeArray();
});

test('grantSecretViewAccess calls grantSecretAccess with viewer role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantSecretViewAccess('123', 'testuser');

    expect($result)->toBeArray();
});

test('grantSecretEditorAccess calls grantSecretAccess with editor role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantSecretEditorAccess('123', 'testuser');

    expect($result)->toBeArray();
});

test('grantSecretAdminAccess calls grantSecretAccess with admin role', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->grantSecretAdminAccess('123', 'testuser');

    expect($result)->toBeArray();
});

test('revokeProjectAccess validates inputs', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->revokeProjectAccess('test-project', 'testuser');

    expect($result)->toBeArray();
});

test('revokeSecretAccess validates inputs', function () {
    $client = createAccessControlMockClient([
        new Response(200, [], json_encode(['success' => true])),
    ]);

    $result = $client->revokeSecretAccess('123', 'testuser');

    expect($result)->toBeArray();
});
