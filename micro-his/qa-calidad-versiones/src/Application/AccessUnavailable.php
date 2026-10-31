<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/** No se pudo decidir la autorización; se falla cerrado (HTTP 503, nunca 200). */
final class AccessUnavailable extends \RuntimeException {}
