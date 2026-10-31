<?php
declare(strict_types=1);
namespace MicroHis\QaRegresion\Application;

use MicroHis\QaRegresion\Domain\{CaseRepository, ManualCase};

final class DesignCase
{
    public function __construct(private readonly CaseRepository $cases) {}

    public function execute(string $purpose, string $steps, string $expected): ManualCase
    {
        $case = new ManualCase(bin2hex(random_bytes(16)), $purpose, $steps, $expected);
        $this->cases->save($case);
        return $case;
    }
}
