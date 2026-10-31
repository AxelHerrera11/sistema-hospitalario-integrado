<?php

declare(strict_types=1);

namespace MicroHisClient\CatalogoLab;

/** El catálogo no respondió: el consumidor no debe ordenar una prueba cuya versión no pudo confirmar. */
final class CatalogUnavailable extends \RuntimeException
{
}
