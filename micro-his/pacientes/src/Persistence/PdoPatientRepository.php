<?php
declare(strict_types=1);
namespace MicroHis\Pacientes\Persistence;

use MicroHis\Pacientes\Application\{PatientRepository,PersistenceFailure};
use MicroHis\Pacientes\Domain\Patient;
use PDO;
use PDOException;

final class PdoPatientRepository implements PatientRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findByCode(string $code): ?Patient
    {
        try {
            $s=$this->pdo->prepare('SELECT local_patient_id,global_id,code,given_name,family_name,birth_date,sex,contact,status FROM local_patients WHERE code=:code');
            $s->execute([':code'=>$code]);
            $r=$s->fetch(PDO::FETCH_ASSOC);
            return $r ? $this->hydrate($r) : null;
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al buscar paciente.',0,$e);
        }
    }

    public function insert(Patient $p): Patient
    {
        try {
            $s=$this->pdo->prepare('INSERT INTO local_patients (global_id,code,given_name,family_name,birth_date,sex,contact,status) VALUES (:global,:code,:given,:family,:birth,:sex,:contact,:status) RETURNING local_patient_id');
            $s->execute($this->params($p));
            return new Patient((string)$s->fetchColumn(),$p->globalId,$p->code,$p->givenName,$p->familyName,$p->birthDate,$p->sex,$p->contact,$p->status);
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al registrar paciente.',0,$e);
        }
    }

    public function update(Patient $p): Patient
    {
        try {
            $s=$this->pdo->prepare('UPDATE local_patients SET given_name=:given,family_name=:family,birth_date=:birth,sex=:sex,contact=:contact,status=:status,updated_at=now() WHERE local_patient_id=:id');
            $v=$this->params($p);
            unset($v[':global'],$v[':code']);
            $v[':id']=$p->id;
            $s->execute($v);
            return $p;
        } catch (PDOException $e) {
            throw new PersistenceFailure('Error al actualizar paciente.',0,$e);
        }
    }

    private function params(Patient $p): array
    {
        return [':global'=>$p->globalId,':code'=>$p->code,':given'=>$p->givenName,
            ':family'=>$p->familyName,':birth'=>$p->birthDate,':sex'=>$p->sex,
            ':contact'=>$p->contact,':status'=>$p->status];
    }

    private function hydrate(array $r): Patient
    {
        return new Patient(
            (string)$r['local_patient_id'],(string)$r['global_id'],(string)$r['code'],
            (string)$r['given_name'],(string)$r['family_name'],(string)$r['birth_date'],
            (string)$r['sex'],$r['contact'] === null ? null : (string)$r['contact'],
            (string)$r['status']
        );
    }
}