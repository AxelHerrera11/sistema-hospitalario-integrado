<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** planned → running → completed | aborted. Completed y aborted son finales. */
enum RunStatus: string
{
    case Planned = 'planned';
    case Running = 'running';
    case Completed = 'completed';
    case Aborted = 'aborted';
}
