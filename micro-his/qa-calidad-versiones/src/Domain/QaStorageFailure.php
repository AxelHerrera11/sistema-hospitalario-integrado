<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** El almacén de ejecuciones no respondió; el caso de uso aborta sin estado parcial (HTTP 503). */
final class QaStorageFailure extends \RuntimeException {}
