<?php

declare(strict_types=1);

foreach ([
    'src/Domain/Documento.php',
    'src/Domain/DocumentoRepository.php',
    'src/Application/PublicarDocumento.php',
    'src/Application/ConsultarDocumentos.php',
    'src/Infrastructure/DocumentoRepositoryInMemory.php',
    'src/Infrastructure/DocumentoRepositoryPDO.php',
    'src/Presentation/DocumentoController.php',
] as $relativo) {
    require_once __DIR__ . '/' . $relativo;
}
