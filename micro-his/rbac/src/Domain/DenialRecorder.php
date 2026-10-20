<?php

declare(strict_types=1);

namespace MicroHis\Rbac\Domain;

interface DenialRecorder
{
    public function record(array $denial): void;
}
