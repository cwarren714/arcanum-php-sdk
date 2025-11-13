<?php

use Arcanum\Sdk\Model\ArcanumProject;
use Arcanum\Sdk\Model\ArcanumUser;
use Arcanum\Sdk\Model\ArcanumEncryptedSecret;
use Arcanum\Sdk\Model\ArcanumVault;

beforeEach(function () {
    $this->owner = new ArcanumUser(1, 'testuser', 'Test User', ['ROLE_USER']);
    $vault = new ArcanumVault(1, 'Test Vault', 'Test vault description');

    $this->secrets = [
        new ArcanumEncryptedSecret(100, 'Secret 1', 'azure-id-1', ['field1', 'field2'], $vault, $this->owner),
        new ArcanumEncryptedSecret(200, 'Secret 2', 'azure-id-2', ['field3'], $vault, $this->owner),
        new ArcanumEncryptedSecret(300, 'API Keys', 'azure-id-3', ['api-key'], $vault, $this->owner),
    ];

    $this->project = new ArcanumProject(
        1,
        'Test Project',
        'A test project',
        'test-project',
        $this->owner,
        $this->secrets
    );
});

test('can create project', function () {
    expect($this->project)->toBeInstanceOf(ArcanumProject::class)
        ->and($this->project->id)->toBe(1)
        ->and($this->project->name)->toBe('Test Project')
        ->and($this->project->description)->toBe('A test project')
        ->and($this->project->slug)->toBe('test-project')
        ->and($this->project->owner)->toBeInstanceOf(ArcanumUser::class)
        ->and($this->project->secrets)->toHaveCount(3);
});

test('hasSecret returns true for existing secret', function () {
    expect($this->project->hasSecret(100))->toBeTrue()
        ->and($this->project->hasSecret(200))->toBeTrue()
        ->and($this->project->hasSecret(300))->toBeTrue();
});

test('hasSecret returns false for non-existent secret', function () {
    expect($this->project->hasSecret(999))->toBeFalse();
});

test('getSecretById returns correct secret', function () {
    $secret = $this->project->getSecretById(100);

    expect($secret)->toBeInstanceOf(ArcanumEncryptedSecret::class)
        ->and($secret->id)->toBe(100)
        ->and($secret->name)->toBe('Secret 1');
});

test('getSecretById returns null for non-existent secret', function () {
    expect($this->project->getSecretById(999))->toBeNull();
});

test('getSecretByName returns correct secret', function () {
    $secret = $this->project->getSecretByName('Secret 2');

    expect($secret)->toBeInstanceOf(ArcanumEncryptedSecret::class)
        ->and($secret->id)->toBe(200)
        ->and($secret->name)->toBe('Secret 2');
});

test('getSecretByName returns null for non-existent secret', function () {
    expect($this->project->getSecretByName('Non-existent'))->toBeNull();
});

test('toArray returns correct structure', function () {
    $array = $this->project->toArray();

    expect($array)->toBeArray()
        ->and($array['id'])->toBe(1)
        ->and($array['name'])->toBe('Test Project')
        ->and($array['description'])->toBe('A test project')
        ->and($array['slug'])->toBe('test-project')
        ->and($array['owner'])->toBeArray()
        ->and($array['secrets'])->toHaveCount(3);
});
