<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Domain;

enum ConnectivityStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
}
