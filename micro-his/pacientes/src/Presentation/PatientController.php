<?php

declare(strict_types=1);

namespace MicroHis\Pacientes\Presentation;

use MicroHis\Pacientes\Application\PatientService;

final class PatientController
{
    private readonly PatientResponsePresenter $presenter;

    public function __construct(
        private readonly PatientService $patients,
        ?PatientResponsePresenter $presenter = null
    ) {
        $this->presenter = $presenter ?? new PatientResponsePresenter();
    }

    public function register(array $data): array
    {
        return $this->presenter->present(
            fn() => $this->patients->register($data),
            201
        );
    }

    public function search(string $code): array
    {
        return $this->presenter->present(
            fn() => $this->patients->search($code)
        );
    }

    public function update(string $code, array $data): array
    {
        return $this->presenter->present(
            fn() => $this->patients->update($code, $data)
        );
    }
}
