<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\EvidenceRef;
use MicroHis\QaCalidad\Domain\InvalidQaInput;
use MicroHis\QaCalidad\Domain\PlannedSuite;
use MicroHis\QaCalidad\Domain\SuiteKind;
use MicroHis\QaCalidad\Domain\SuiteResult;
use MicroHis\QaCalidad\Domain\TestEnvironment;
use MicroHis\QaCalidad\Domain\TestPlan;

/** Convierte cuerpos JSON ya decodificados en objetos de dominio (422 si no cuadran). */
final class RunInputParser
{
    public const MAX_SUITES = 30;

    public const MAX_EVIDENCE = 20;

    /** @return array{TestPlan, TestEnvironment} */
    public function plan(array $body): array
    {
        $suites = $body['suites'] ?? null;
        if (! is_array($suites) || $suites === [] || count($suites) > self::MAX_SUITES || ! array_is_list($suites)) {
            throw new InvalidQaInput('suites_invalidas', 'suites debe ser una lista de 1 a '.self::MAX_SUITES.' elementos.');
        }
        $planned = array_map(fn (mixed $s): PlannedSuite => $this->suite($s), $suites);

        return [
            new TestPlan($this->string($body, 'release_version'), $this->string($body, 'commit_sha'), $planned),
            TestEnvironment::parse($body['environment'] ?? null),
        ];
    }

    private function suite(mixed $data): PlannedSuite
    {
        $required = is_array($data) ? ($data['required'] ?? true) : null;
        if (! is_bool($required)) {
            // "false" como texto no debe convertir en opcional una suite: se rechaza.
            throw new InvalidQaInput('campo_obligatorio', 'required debe ser booleano.');
        }

        return new PlannedSuite($this->string($data, 'name'), SuiteKind::parse($data['kind'] ?? null), $required);
    }

    public function result(array $body): SuiteResult
    {
        $evidence = $body['evidence'] ?? [];
        if (! is_array($evidence) || ! array_is_list($evidence) || count($evidence) > self::MAX_EVIDENCE) {
            throw new InvalidQaInput('evidencia_invalida', 'evidence debe ser una lista de hasta '.self::MAX_EVIDENCE.' elementos.');
        }

        return new SuiteResult(
            $this->string($body, 'suite'),
            $this->int($body, 'total'),
            $this->int($body, 'failed'),
            $this->int($body, 'skipped'),
            $this->int($body, 'duration_ms'),
            array_map(fn (mixed $e): EvidenceRef => new EvidenceRef(
                $this->string($e, 'kind'),
                $this->string($e, 'path'),
                $this->string($e, 'sha256'),
                $this->int($e, 'bytes'),
            ), $evidence),
        );
    }

    private function string(mixed $data, string $key): string
    {
        $value = is_array($data) ? ($data[$key] ?? null) : null;
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidQaInput('campo_obligatorio', "El campo {$key} es obligatorio y debe ser texto.");
        }

        return trim($value);
    }

    private function int(mixed $data, string $key): int
    {
        $value = is_array($data) ? ($data[$key] ?? null) : null;
        if (! is_int($value)) {
            throw new InvalidQaInput('campo_obligatorio', "El campo {$key} es obligatorio y debe ser entero.");
        }

        return $value;
    }
}
