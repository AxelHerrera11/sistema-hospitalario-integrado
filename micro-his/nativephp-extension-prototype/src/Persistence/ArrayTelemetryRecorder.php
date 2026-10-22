<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Persistence;

use MicroHis\NativePhpPrototype\Application\TelemetryRecorder;

final class ArrayTelemetryRecorder implements TelemetryRecorder
{
    /** @var array<int, array<string, string|int|null>> */
    public array $events = [];

    /** @param array<string, string|int|null> $event */
    public function record(array $event): void
    {
        $this->events[] = $event;
    }
}
