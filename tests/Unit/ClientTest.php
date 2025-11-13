<?php

namespace Tests\Unit;

use Arcanum\Sdk\Client;
use Arcanum\Sdk\Model\ArcanumDecryptedSecret;
use Arcanum\Sdk\Model\ArcanumSecretField;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$client = new Client($_ENV['ARCANUM_API_KEY'], $_ENV['ARCANUM_API_SECRET'], $_ENV['ARCANUM_API_BASE_URL']);
test('client', function () use ($client) {
    expect($client)->toBeInstanceOf(Client::class);
});

test('client with invalid api key throws exception', function () {
    expect(fn() => new Client('', $_ENV['ARCANUM_API_SECRET'], $_ENV['ARCANUM_API_BASE_URL']))
        ->toThrow(\InvalidArgumentException::class, 'API Key and Secret are required');
});

test('client can list secrets', function () use ($client) {
    $secrets = $client->listSecrets();
    expect($secrets)->toBeArray()->dump();
});

test('client can get individual secret', function () use ($client) {
    $secret = $client->getSecret('152');
    expect($secret)->toBeInstanceOf(ArcanumDecryptedSecret::class)->dump();
    $field = $secret->getField('test-field');
    expect($field)->toBeInstanceOf(ArcanumSecretField::class)->dump();
});

test('client can get secret by name', function () use ($client) {
    $test_secret = $client->getSecretByName('TEST_SECRET');
    $actual_secret = $client->getSecret($test_secret->id);
    expect($actual_secret)->toBeInstanceOf(ArcanumDecryptedSecret::class)->dump();
});

test('client can list projects', function () use ($client) {
    $projects = $client->listProjects();
    expect($projects)->toBeArray()->dump();
});
