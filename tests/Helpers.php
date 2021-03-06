<?php

namespace Tests;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

function useCleanupConfig(Closure $closure, string $model)
{
    $model::setCleanupConfigClosure($closure);

    config()->set('model-cleanup.models', [
        $model,
    ]);
}

function assertDeleteQueriesExecuted(int $expectedCount)
{
    $actualCount = collect(DB::getQueryLog())
        ->map(function (array $queryProperties) {
            return $queryProperties['query'];
        })
        ->filter(function (string $query) {
            return Str::startsWith($query, 'delete');
        })
        ->count();

    assertEquals(
        $expectedCount,
        $actualCount,
        "Expected {$expectedCount} delete queries, but {$actualCount} delete queries where executed."
    );
}

function assertModelsExistForDays(array $expectedDates, string $model)
{
    $actualDates = $model::all()
        ->pluck('created_at')
        ->map(fn (Carbon $createdAt) => $createdAt->format('Y-m-d'))
        ->toArray();

    assertEquals($expectedDates, $actualDates);
}

function assertModelsWithTrashedExistForDays(array $expectedDates, string $model)
{
    $actualDates = $model::withTrashed()
        ->pluck('created_at')
        ->map(fn (Carbon $createdAt) => $createdAt->format('Y-m-d'))
        ->toArray();

    assertEquals($expectedDates, $actualDates);
}

function assertExceptionThrown(
    callable $callable,
    string $expectedExceptionClass = Exception::class
): void {
    try {
        $callable();

        assertTrue(false, "Expected exception `{$expectedExceptionClass}` was not thrown.");
    } catch (Exception $exception) {
        $actualExceptionClass = get_class($exception);

        assertInstanceOf($expectedExceptionClass, $exception, "Unexpected exception `$actualExceptionClass` thrown. Expected exception `$expectedExceptionClass`");
    }
}
