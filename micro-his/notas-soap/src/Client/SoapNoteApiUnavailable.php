<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Client;

/** El servidor de notas SOAP no respondió (caído o timeout). */
final class SoapNoteApiUnavailable extends \RuntimeException
{
}
