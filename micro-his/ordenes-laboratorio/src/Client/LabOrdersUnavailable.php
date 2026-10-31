<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Client;

/** El servidor no respondió o falló de forma no recuperable: la operación no se puede confirmar. */
final class LabOrdersUnavailable extends \RuntimeException
{
}
