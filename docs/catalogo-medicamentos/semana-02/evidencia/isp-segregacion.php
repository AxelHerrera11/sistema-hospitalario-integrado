<?php

declare(strict_types=1);

// Evidencia ISP de Semana 2: mide cuántos métodos del contrato usa cada cliente.
// No es la implementación del módulo (empieza en Semana 3); usa datos ficticios.

namespace Asii14\Antes {
    interface MedicationCatalogService
    {
        public function register(array $draft): array;
        public function search(string $term): array;
        public function findById(string $id): ?array;
        public function changeValidity(string $id, string $validity): array;
    }

    final class AltaClient
    {
        public function __construct(private MedicationCatalogService $catalog) {}
        public function run(): void { $this->catalog->register(['code' => 'DEMO-001']); }
    }

    final class BusquedaClient
    {
        public function __construct(private MedicationCatalogService $catalog) {}
        public function run(): void { $this->catalog->search('demo'); $this->catalog->findById('1'); }
    }

    final class VigenciaClient
    {
        public function __construct(private MedicationCatalogService $catalog) {}
        public function run(): void { $this->catalog->changeValidity('1', 'inactive'); }
    }

    final class Recorder implements MedicationCatalogService
    {
        public array $calls = [];
        public function register(array $draft): array { $this->calls[] = __FUNCTION__; return $draft; }
        public function search(string $term): array { $this->calls[] = __FUNCTION__; return []; }
        public function findById(string $id): ?array { $this->calls[] = __FUNCTION__; return null; }
        public function changeValidity(string $id, string $validity): array { $this->calls[] = __FUNCTION__; return []; }
    }
}

namespace Asii14\Despues {
    interface MedicationRegistration
    {
        public function register(array $draft): array;
    }

    interface MedicationLookup
    {
        public function search(string $term): array;
        public function findById(string $id): ?array;
    }

    interface MedicationValidityControl
    {
        public function changeValidity(string $id, string $validity): array;
    }

    final class AltaClient
    {
        public function __construct(private MedicationRegistration $registration) {}
        public function run(): void { $this->registration->register(['code' => 'DEMO-001']); }
    }

    final class BusquedaClient
    {
        public function __construct(private MedicationLookup $lookup) {}
        public function run(): void { $this->lookup->search('demo'); $this->lookup->findById('1'); }
    }

    final class VigenciaClient
    {
        public function __construct(private MedicationValidityControl $validity) {}
        public function run(): void { $this->validity->changeValidity('1', 'inactive'); }
    }

    final class Recorder implements MedicationRegistration, MedicationLookup, MedicationValidityControl
    {
        public array $calls = [];
        public function register(array $draft): array { $this->calls[] = __FUNCTION__; return $draft; }
        public function search(string $term): array { $this->calls[] = __FUNCTION__; return []; }
        public function findById(string $id): ?array { $this->calls[] = __FUNCTION__; return null; }
        public function changeValidity(string $id, string $validity): array { $this->calls[] = __FUNCTION__; return []; }
    }

    final class LookupOnly implements MedicationLookup
    {
        public function search(string $term): array { return []; }
        public function findById(string $id): ?array { return null; }
    }
}

namespace {
    /** Devuelve [métodos del contrato del que depende el cliente, métodos invocados]. */
    function measure(string $client, string $recorder): array
    {
        $contract = (new ReflectionClass($client))->getConstructor()->getParameters()[0]->getType()->getName();
        $methods = array_map(static fn (ReflectionMethod $m): string => $m->getName(), (new ReflectionClass($contract))->getMethods());
        $double = new $recorder();
        (new $client($double))->run();

        return [$contract, $methods, array_values(array_unique($double->calls))];
    }

    $unused = ['Antes' => 0, 'Despues' => 0];
    $readMethods = [];
    foreach (array_keys($unused) as $design) {
        echo "== Diseño {$design} ==\n";
        foreach (['AltaClient', 'BusquedaClient', 'VigenciaClient'] as $name) {
            [$contract, $methods, $used] = measure("Asii14\\{$design}\\{$name}", "Asii14\\{$design}\\Recorder");
            $missing = array_diff($methods, $used);
            $unused[$design] += count($missing);
            if ($design === 'Despues' && $name === 'BusquedaClient') {
                $readMethods = $methods;
            }
            printf("%-15s -> %-26s depende de %d, usa %d, sin usar %d [%s]\n", $name, substr(strrchr($contract, '\\'), 1),
                count($methods), count($used), count($missing), implode(', ', $missing));
        }
    }

    try {
        new Asii14\Despues\AltaClient(new Asii14\Despues\LookupOnly());
        $rejected = false;
    } catch (TypeError) {
        $rejected = true;
    }

    $checks = [
        'antes: los clientes dependen de 8 métodos que no usan' => $unused['Antes'] === 8,
        'después: 0 métodos sin usar en todos los clientes' => $unused['Despues'] === 0,
        'después: el contrato de lectura no expone escrituras' => array_intersect($readMethods, ['register', 'changeValidity']) === [],
        'después: un objeto solo de lectura no sirve para el alta (TypeError)' => $rejected,
    ];
    echo "\n";
    foreach ($checks as $label => $ok) {
        echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n";
    }
    $failed = count(array_filter($checks, static fn (bool $ok): bool => !$ok));
    echo $failed === 0 ? "OK: 4 verificaciones aprobadas\n" : "ERROR: {$failed} verificaciones fallaron\n";
    exit($failed === 0 ? 0 : 1);
}
