<?php

use Arcanum\Sdk\Exception\ApiException;
use Arcanum\Sdk\Exception\NotFoundException;
use Arcanum\Sdk\Exception\UnauthorizedException;
use Arcanum\Sdk\Exception\ValidationException;
use Arcanum\Sdk\Exception\RateLimitException;

test('ApiException can be created and thrown', function () {
    expect(fn() => throw new ApiException('Test error', 500))
        ->toThrow(ApiException::class, 'Test error');
});

test('ApiException extends RuntimeException', function () {
    $exception = new ApiException('Test error', 500);

    expect($exception)->toBeInstanceOf(\RuntimeException::class)
        ->and($exception->getMessage())->toBe('Test error')
        ->and($exception->getCode())->toBe(500);
});

test('NotFoundException extends ApiException', function () {
    $exception = new NotFoundException('Resource not found', 404);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception)->toBeInstanceOf(\RuntimeException::class)
        ->and($exception->getMessage())->toBe('Resource not found')
        ->and($exception->getCode())->toBe(404);
});

test('UnauthorizedException extends ApiException', function () {
    $exception = new UnauthorizedException('Unauthorized', 401);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception)->toBeInstanceOf(\RuntimeException::class)
        ->and($exception->getMessage())->toBe('Unauthorized')
        ->and($exception->getCode())->toBe(401);
});

test('ValidationException extends ApiException and stores errors', function () {
    $errors = ['field1' => 'Field 1 is required', 'field2' => 'Field 2 is invalid'];
    $exception = new ValidationException('Validation failed', 422, null, $errors);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception->getMessage())->toBe('Validation failed')
        ->and($exception->getCode())->toBe(422)
        ->and($exception->getErrors())->toBe($errors)
        ->and($exception->getErrors())->toHaveKey('field1')
        ->and($exception->getErrors())->toHaveKey('field2');
});

test('ValidationException can be created without errors', function () {
    $exception = new ValidationException('Validation failed', 422);

    expect($exception->getErrors())->toBeArray()
        ->and($exception->getErrors())->toBeEmpty();
});

test('RateLimitException extends ApiException and stores retry after', function () {
    $exception = new RateLimitException('Rate limit exceeded', 429, null, 60);

    expect($exception)->toBeInstanceOf(ApiException::class)
        ->and($exception->getMessage())->toBe('Rate limit exceeded')
        ->and($exception->getCode())->toBe(429)
        ->and($exception->getRetryAfter())->toBe(60);
});

test('RateLimitException can be created without retry after', function () {
    $exception = new RateLimitException('Rate limit exceeded', 429);

    expect($exception->getRetryAfter())->toBeNull();
});

test('exceptions can have previous exception', function () {
    $previous = new \Exception('Previous error');
    $exception = new ApiException('Current error', 500, $previous);

    expect($exception->getPrevious())->toBe($previous)
        ->and($exception->getPrevious()->getMessage())->toBe('Previous error');
});
