<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Domain;

enum ReviewStatus: string
{
    case PendingReview = 'pendiente_revision';
    case Validated = 'validado';
    case Rejected = 'rechazado';
}
