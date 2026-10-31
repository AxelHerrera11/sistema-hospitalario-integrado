<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Client;

/** El servidor de signos vitales no respondió (caído o timeout). */
final class VitalSignApiUnavailable extends \RuntimeException
{
}
