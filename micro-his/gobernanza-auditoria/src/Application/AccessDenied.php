<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

final class AccessDenied extends \RuntimeException
{
    public function __construct(public readonly AccessDecision $decision)
    {
        parent::__construct('access_denied');
    }
}
