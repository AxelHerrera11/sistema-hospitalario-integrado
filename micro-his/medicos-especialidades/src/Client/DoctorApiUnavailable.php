<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Client;

/** El servidor no respondió (caído o tiempo agotado). El consumidor decide: mostrar "no disponible", nunca inventar datos. */
final class DoctorApiUnavailable extends \RuntimeException
{
}
