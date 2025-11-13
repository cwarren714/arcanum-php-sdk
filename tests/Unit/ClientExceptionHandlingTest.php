<?php

use Arcanum\Sdk\Client;
use Arcanum\Sdk\Exception\ApiException;
use Arcanum\Sdk\Exception\NotFoundException;
use Arcanum\Sdk\Exception\UnauthorizedException;
use Arcanum\Sdk\Exception\ValidationException;
use Arcanum\Sdk\Exception\RateLimitException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

function createExceptionMockClient(array $responses): Client
{
    $mock = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mock);
    $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

    return new Client('test-key', 'test-secret', 'https://test.api', $guzzleClient);
}

test('client throws NotFoundException for 404 response', function () {
    $client = createExceptionMockClient([
        new Response(404, [], json_encode(['message' => 'Resource not found'])),
    ]);

    expect(fn() => $client->getProject('non-existent'))
        ->toThrow(NotFoundException::class);
});

test('client throws UnauthorizedException for 401 response', function () {
    $client = createExceptionMockClient([
        new Response(401, [], json_encode(['message' => 'Unauthorized'])),
    ]);

    expect(fn() => $client->listVaults())
        ->toThrow(UnauthorizedException::class);
});

test('client throws UnauthorizedException for 403 response', function () {
    $client = createExceptionMockClient([
        new Response(403, [], json_encode(['message' => 'Forbidden'])),
    ]);

    expect(fn() => $client->listVaults())
        ->toThrow(UnauthorizedException::class);
});

test('client throws ValidationException for 400 response', function () {
    $client = createExceptionMockClient([
        new Response(400, [], json_encode([
            'message' => 'Validation failed',
            'errors' => ['field1' => 'Field is required']
        ])),
    ]);

    expect(fn() => $client->createSecret(['invalidData' => 'test']))
        ->toThrow(ValidationException::class);
});

test('client throws ValidationException for 422 response', function () {
    $client = createExceptionMockClient([
        new Response(422, [], json_encode([
            'message' => 'Unprocessable entity',
            'errors' => ['name' => 'Name is invalid']
        ])),
    ]);

    expect(fn() => $client->createProject(['name' => 'Invalid']))
        ->toThrow(ValidationException::class);
});

test('ValidationException includes error details', function () {
    $client = createExceptionMockClient([
        new Response(422, [], json_encode([
            'message' => 'Validation failed',
            'errors' => ['field1' => 'Field 1 error', 'field2' => 'Field 2 error']
        ])),
    ]);

    try {
        $client->createSecret(['invalidData' => 'test']);
    } catch (ValidationException $e) {
        expect($e->getErrors())->toHaveKey('field1')
            ->and($e->getErrors())->toHaveKey('field2')
            ->and($e->getErrors()['field1'])->toBe('Field 1 error');
    }
});

test('client throws RateLimitException for 429 response', function () {
    $client = createExceptionMockClient([
        new Response(429, ['Retry-After' => '60'], json_encode(['message' => 'Rate limit exceeded'])),
    ]);

    expect(fn() => $client->listVaults())
        ->toThrow(RateLimitException::class);
});

test('RateLimitException includes retry after header', function () {
    $client = createExceptionMockClient([
        new Response(429, ['Retry-After' => '120'], json_encode(['message' => 'Rate limit exceeded'])),
    ]);

    try {
        $client->listVaults();
    } catch (RateLimitException $e) {
        expect($e->getRetryAfter())->toBe(120);
    }
});

test('client throws generic ApiException for 500 response', function () {
    $client = createExceptionMockClient([
        new Response(500, [], json_encode(['message' => 'Internal server error'])),
    ]);

    try {
        $client->listVaults();
        expect(false)->toBeTrue();
    } catch (ApiException $e) {
        expect($e)->toBeInstanceOf(ApiException::class)
            ->and($e)->not->toBeInstanceOf(NotFoundException::class)
            ->and($e)->not->toBeInstanceOf(UnauthorizedException::class)
            ->and($e)->not->toBeInstanceOf(ValidationException::class)
            ->and($e)->not->toBeInstanceOf(RateLimitException::class)
            ->and($e->getCode())->toBe(500);
    }
});

test('exception message includes API message', function () {
    $client = createExceptionMockClient([
        new Response(404, [], json_encode(['message' => 'Project not found'])),
    ]);

    try {
        $client->getProject('non-existent');
    } catch (NotFoundException $e) {
        expect($e->getMessage())->toContain('Project not found');
    }
});

test('exception handles empty response body', function () {
    $client = createExceptionMockClient([
        new Response(404, [], ''),
    ]);

    expect(fn() => $client->getProject('non-existent'))
        ->toThrow(NotFoundException::class);
});

test('exception handles non-JSON response body', function () {
    $client = createExceptionMockClient([
        new Response(500, [], 'Internal Server Error'),
    ]);

    try {
        $client->listVaults();
    } catch (ApiException $e) {
        expect($e->getMessage())->toContain('Internal Server Error');
    }
});
