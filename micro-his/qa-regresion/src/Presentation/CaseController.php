<?php
declare(strict_types=1);
namespace MicroHis\QaRegresion\Presentation;

use MicroHis\QaRegresion\Application\DesignCase;
use MicroHis\QaRegresion\Domain\StorageFailure;

final class CaseController
{
    public function __construct(private readonly DesignCase $design) {}
    public function create(mixed $input): array
    {
        foreach (['purpose', 'steps', 'expected'] as $key) {
            if (!is_array($input) || !isset($input[$key]) || !is_string($input[$key])) {
                return ['status' => 400, 'body' => ['error' => 'INVALID_INPUT']];
            }
        }
        try {
            return CaseView::created($this->design->execute($input['purpose'], $input['steps'], $input['expected']));
        } catch (\DomainException) {
            return ['status' => 422, 'body' => ['error' => 'CASE_INCOMPLETE']];
        } catch (StorageFailure) {
            return ['status' => 503, 'body' => ['error' => 'CASE_STORAGE_UNAVAILABLE']];
        }
    }
}
