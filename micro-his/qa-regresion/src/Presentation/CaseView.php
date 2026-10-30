<?php
declare(strict_types=1);
namespace MicroHis\QaRegresion\Presentation;

use MicroHis\QaRegresion\Domain\ManualCase;

final class CaseView
{
    public static function created(ManualCase $case): array
    {
        return ['status' => 201, 'body' => ['data' => get_object_vars($case)]];
    }
}
