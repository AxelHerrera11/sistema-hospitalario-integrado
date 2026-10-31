<?php
declare(strict_types=1);
namespace MicroHis\QaRegresion\Domain;

final readonly class ManualCase
{
    public string $purpose;
    public string $steps;
    public string $expected;

    public function __construct(public string $id, string $purpose, string $steps, string $expected)
    {
        $this->purpose = trim($purpose);
        $this->steps = trim($steps);
        $this->expected = trim($expected);
        if ($id === '' || $this->purpose === '' || $this->steps === '' || $this->expected === '') {
            throw new \DomainException('Identidad, propósito, pasos y resultado esperado son obligatorios.');
        }
    }
}
