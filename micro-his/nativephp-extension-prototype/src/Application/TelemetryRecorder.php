<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Application;

interface TelemetryRecorder
{
    /** @param array<string, string|int|null> $event */
    public function record(array $event): void;
}
