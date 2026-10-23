<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * approved: la versión puede promoverse.
 * rejected: hay evidencia de que la versión falla (pruebas en rojo).
 * blocked:  no hay evidencia suficiente para decidir (suite ausente, vacía o sin evidencia).
 */
enum GateVerdict: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Blocked = 'blocked';
}
