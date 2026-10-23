<?php

declare(strict_types=1);
/*
 * Micro-HIS Centro de Documentacion (ASII-24, Semana 3) - bootstrap sin Composer.
 */

foreach ([
    __DIR__ . '/src/Domain/Documento.php',
    __DIR__ . '/src/Domain/DocumentoRepository.php',
    __DIR__ . '/src/Application/ConsultarDocumentos.php',
    __DIR__ . '/src/Application/PublicarDocumento.php',
    __DIR__ . '/src/Persistence/DocumentoRepositoryPDO.php',
    __DIR__ . '/src/Persistence/DocumentoRepositoryEnMemoria.php',
] as $archivo) {
    require_once $archivo;
}
