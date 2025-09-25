<?php

declare(strict_types=1);

use Tailstream\LaravelLogger\Batch\CacheBatchProcessor;

beforeEach(function () {
    $this->processor = app(CacheBatchProcessor::class);
});

test('flush command shows status when no pending entries', function () {
    $this->artisan('tailstream:flush --status')
        ->expectsTable(['Metric', 'Value'], [
            ['Pending Entries', 0],
            ['Status', 'No pending logs'],
            ['Cache Driver', 'array'],
            ['Batch Size Limit', 5],
            ['Time Threshold', '1 seconds'],
        ])
        ->assertSuccessful();
});

test('flush command shows status with pending entries', function () {
    $this->processor->add(['message' => 'Test entry']);

    $this->artisan('tailstream:flush --status')
        ->expectsTable(['Metric', 'Value'], [
            ['Pending Entries', 1],
            ['Status', 'Has pending logs'],
            ['Cache Driver', 'array'],
            ['Batch Size Limit', 5],
            ['Time Threshold', '1 seconds'],
        ])
        ->expectsOutput("Use 'php artisan tailstream:flush' to process pending entries.")
        ->assertSuccessful();
});

test('flush command processes pending entries', function () {
    $this->processor->add(['message' => 'Test entry']);

    $this->artisan('tailstream:flush')
        ->expectsOutput('Flushing 1 pending log entries...')
        ->expectsOutputToContain('✅ Successfully flushed 1 log entries in')
        ->assertSuccessful();

    expect($this->processor->getBatchSize())->toBe(0);
});

test('flush command handles no pending entries gracefully', function () {
    $this->artisan('tailstream:flush')
        ->expectsOutput('No pending entries to flush.')
        ->assertSuccessful();
});

test('clear command clears pending entries with confirmation', function () {
    $this->processor->add(['message' => 'Test entry']);

    $this->artisan('tailstream:flush --clear')
        ->expectsConfirmation('Are you sure you want to clear 1 pending log entries?', 'yes')
        ->expectsOutput('Cleared 1 pending log entries.')
        ->assertSuccessful();

    expect($this->processor->getBatchSize())->toBe(0);
});

test('clear command can be cancelled', function () {
    $this->processor->add(['message' => 'Test entry']);

    $this->artisan('tailstream:flush --clear')
        ->expectsConfirmation('Are you sure you want to clear 1 pending log entries?', 'no')
        ->expectsOutput('Clear operation cancelled.')
        ->assertSuccessful();

    expect($this->processor->getBatchSize())->toBe(1);
});

test('clear command handles no pending entries', function () {
    $this->artisan('tailstream:flush --clear')
        ->expectsOutput('No pending entries to clear.')
        ->assertSuccessful();
});
