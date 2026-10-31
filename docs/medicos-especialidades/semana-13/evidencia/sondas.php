<?php
declare(strict_types=1);
// Sondas de la revision tecnica (Semana 13). Uso: php sondas.php <ruta a micro-his/medicos-especialidades>. No modifica el modulo.
use MicroHis\Medicos\Persistence\{PdoDoctorStore, ConfigScheduleProvider};
use MicroHis\Medicos\Presentation\{ApiKernel, Request};
use MicroHis\Medicos\Application\DoctorProfileStore;
require $argv[1] . '/app.php';
function mk(): array {
    global $argv;
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec(file_get_contents($argv[1] . '/database/schema.sql'));
    $pdo->exec("INSERT INTO specialties VALUES ('sp-card','CARD','active'),('sp-neuro','NEURO','active'),('sp-ped','PED','inactive')");
    $pdo->exec("INSERT INTO doctor_profiles VALUES ('doc-ana','u-a','COL-9','active','2026-01-01',NULL)");
    $pdo->exec("INSERT INTO doctor_specialties VALUES ('doc-ana','sp-neuro',1,'2026-01-01',NULL,'active')");
    $s = new PdoDoctorStore($pdo);
    return [$pdo, $s, medicos_router($s, $s, new ConfigScheduleProvider(['doc-ana' => [1 => [['08:00','10:00']]]]))];
}
function call($r, $m, $p, $b = [], $q = []) { $x = $r->dispatch(new Request($m, $p, ['X-User-ID' => 'a1'], $q, $b)); return $x['status'] . ' ' . $x['body']; }
$alta = ['local_user_ref_id'=>'u-x','license_number'=>'COL-1','specialty_code'=>'CARD','valid_from'=>'2026-01-01'];

// P1: cita de 60 min (08:00-09:00): el esquema solo guarda starts_at
[$pdo,,$r] = mk();
$pdo->exec("INSERT INTO appointments VALUES ('doc-ana','2026-10-19 08:00:00','confirmed')");
echo "P1 cita 08:00 (dura 60 min en la BD real) -> ", call($r,'GET','/availability',[],['specialty'=>'NEURO','date'=>'2026-10-19']), "\n";

// P2: carrera en la licencia: licenseExists() dijo false pero otro hilo insertó antes
[$pdo,$s,] = mk();
$pdo->exec("INSERT INTO doctor_profiles VALUES ('doc-z','u-z','COL-1','active','2026-01-01',NULL)");
$racy = new class($s) implements DoctorProfileStore { function __construct(private $s){} function licenseExists(string $l): bool { return false; } function createDoctor(string $u,string $l,\MicroHis\Medicos\Domain\SpecialtyLink $p,string $a): string { return $this->s->createDoctor($u,$l,$p,$a);} };
try { (new \MicroHis\Medicos\Application\RegisterDoctor($racy, $s))->execute('a1','u-x','COL-1','CARD','2026-01-01'); echo "P2 sin error\n"; }
catch (\Throwable $e) { echo "P2 carrera de licencia -> ", get_class($e), ' ', $e->getMessage(), " (el handler lo traduce a 500, no a 409)\n"; }

// P3: mismo usuario local dos perfiles
[$pdo,,$r] = mk();
echo "P3a alta u-x -> ", call($r,'POST','/doctors',$alta), "\n";
echo "P3b alta u-x con otra licencia -> ", call($r,'POST','/doctors',['license_number'=>'COL-2']+$alta), "\n";

// P4: dos principales solapadas directo en BD (el subconjunto SQLite no tiene EXCLUDE)
[$pdo,,$r] = mk();
$pdo->exec("INSERT INTO doctor_specialties VALUES ('doc-ana','sp-card',1,'2026-01-01',NULL,'active')");
echo "P4 BD acepta 2 principales solapadas sin restriccion -> filas principales: ", $pdo->query("SELECT count(*) FROM doctor_specialties WHERE doctor_profile_id='doc-ana' AND is_primary=1")->fetchColumn(), "\n";

// P5: tabla de permisos vs rutas
foreach ([['GET','/specialties'],['GET','/doctors/doc-ana'],['DELETE','/doctors/doc-ana'],['POST','/doctors/doc-ana/specialties']] as [$m,$p]) echo "P5 permissionFor($m $p) = ", var_export(ApiKernel::permissionFor($m,$p), true), "\n";

// P6/P7: orden de validaciones y parametro ausente
[$pdo,,$r] = mk();
echo "P6 availability sin specialty -> ", call($r,'GET','/availability',[],['date'=>'2026-10-19']), "\n";
echo "P7 medico inexistente + especialidad inactiva -> ", call($r,'POST','/doctors/doc-nada/specialties',['specialty_code'=>'PED','valid_from'=>'2026-02-01']), "\n";
echo "P7b medico inexistente + especialidad valida -> ", call($r,'POST','/doctors/doc-nada/specialties',['specialty_code'=>'CARD','valid_from'=>'2026-02-01']), "\n";
// P8: dia sin horario vs dia libre
echo "P8 martes (dia sin franjas) -> ", call($r,'GET','/availability',[],['specialty'=>'NEURO','date'=>'2026-10-20']), "\n";
// P9: fecha pasada
echo "P9 fecha 1999 -> ", call($r,'GET','/availability',[],['specialty'=>'NEURO','date'=>'1999-01-04']), "\n";
// P10: ids
echo "P10 id generado: ", json_decode(substr(call($r,'POST','/doctors',$alta),4),true)['doctor_profile_id'], " (la BD real usa uuid)\n";
