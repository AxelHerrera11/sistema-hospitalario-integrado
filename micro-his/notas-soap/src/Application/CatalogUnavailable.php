<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Application;

/** El catálogo CIE-10 no respondió: se aplica falla segura (RF-SOAP-06). */
final class CatalogUnavailable extends \RuntimeException
{
}
