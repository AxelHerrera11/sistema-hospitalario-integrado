<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

/** La ejecución no existe en el tenant del actor (HTTP 404; también para otro tenant). */
final class RunNotFound extends \RuntimeException {}
