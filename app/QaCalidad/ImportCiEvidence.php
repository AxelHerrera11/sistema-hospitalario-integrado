<?php

declare(strict_types=1);

namespace App\QaCalidad;

use Illuminate\Console\Command;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Application\QaPermissions;
use MicroHis\QaCalidad\Ci\EvidenceCollector;
use MicroHis\QaCalidad\Ci\GateRunner;
use MicroHis\QaCalidad\Ci\JUnitReportReader;
use MicroHis\QaCalidad\Ci\ModuleFactory;
use MicroHis\QaCalidad\Domain\TestRunRepository;
use MicroHis\QaCalidad\Integration\GobernanzaAuditTrail;
use MicroHis\QaCalidad\Integration\StaticAccessGate;
use MicroHis\QaCalidad\Integration\SystemClock;
use MicroHis\QaCalidad\QaModule;
use Throwable;

/**
 * Lleva una ejecución automatizada de CI a /calidad-versiones: lee el paquete de evidencias (reportes JUnit y
 * archivos con su SHA-256) y recorre planificar → iniciar → registrar cada suite → completar → quality gate con los
 * MISMOS casos de uso que la API y quality-gate.php, sobre el almacén de la aplicación y con la bitácora real de
 * Gobernanza. La cuenta de servicio solo existe en este proceso de consola (lo ejecuta quien opera el servidor);
 * por HTTP sigue mandando RBAC. Salida: 0 aprobado, 1 rechazado, 2 bloqueado, 3 error de configuración.
 */
final class ImportCiEvidence extends Command
{
    protected $signature = 'qa:import-evidence
        {evidence-root : Carpeta del paquete de evidencias (qa-evidence del workflow o de pipeline-local.sh)}
        {--sha= : Commit evaluado (40 caracteres hexadecimales)}
        {--release= : Versión SemVer, p. ej. 1.4.0-rc.2}
        {--environment=ci : local, ci o staging}
        {--hospital= : Hospital dueño de la ejecución; por defecto HOSPITAL_DB_TENANT_ID}
        {--manifest= : Suites esperadas; por defecto config/ci-manifest.json del módulo}';

    protected $description = 'Importa la evidencia de un pipeline de CI como ejecución con quality gate en /calidad-versiones';

    public function handle(TestRunRepository $runs, RecordAuditEvent $audit): int
    {
        $base = base_path('micro-his/qa-calidad-versiones');
        try {
            $hospital = (string) ($this->option('hospital') ?: config('tenants.hospital_database_tenant_id'));
            $root = (string) $this->argument('evidence-root');
            $manifest = json_decode((string) @file_get_contents($this->option('manifest') ?: $base.'/config/ci-manifest.json'), true);
            if ($hospital === '' || ! is_dir($root) || ! is_array($manifest['suites'] ?? null)) {
                throw new \RuntimeException('Hospital, carpeta de evidencias o manifiesto inválidos.');
            }
            // El manifiesto de CI apunta al hospital de pruebas; la importación pertenece al hospital de esta instancia.
            $manifest['tenant_id'] = $hospital;
            $ci = new Actor((string) ($manifest['ci_actor'] ?? 'ci-bot'), $hospital);
            $module = QaModule::compose(
                $runs,
                new StaticAccessGate([$ci->userId.'@'.$hospital => [QaPermissions::VIEW, QaPermissions::REGISTER, QaPermissions::EVALUATE]]),
                new GobernanzaAuditTrail($audit),
                new SystemClock,
                ModuleFactory::policy($base.'/config/quality-gate.json'),
                fn (string $code) => $this->error('qa_calidad.'.$code),
            );
            $result = (new GateRunner($module, new JUnitReportReader, new EvidenceCollector($root)))->run(
                $ci,
                $manifest,
                (string) ($this->option('release') ?? throw new \RuntimeException('Falta --release.')),
                (string) ($this->option('sha') ?? throw new \RuntimeException('Falta --sha.')),
                (string) $this->option('environment'),
                'ci-import-'.bin2hex(random_bytes(6)),
            );
        } catch (Throwable $e) {
            $this->error('No se importó la evidencia: '.$e->getMessage());

            return 3;
        }

        $verdict = $result['decision']->verdict->value;
        $this->info("Ejecución {$result['run']->id} importada en el hospital {$hospital}: quality gate {$verdict}.");
        foreach ($result['notes'] as $note) {
            $this->line('  - '.$note);
        }

        return match ($verdict) {
            'approved' => 0, 'rejected' => 1, default => 2,
        };
    }
}
