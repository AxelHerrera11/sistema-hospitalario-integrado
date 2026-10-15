<?php

declare(strict_types=1);

namespace MicroHis\NativePhpPrototype\Domain;

enum SyncStatus: string
{
    case Pending = 'pendiente_sincronizar';
    case Synced = 'sincronizado';
    case Failed = 'fallido';
}
