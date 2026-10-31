<?php

declare(strict_types=1);

namespace App\GobernanzaAuditoria;

use PDO;

/**
 * Opciones de los filtros de /auditoria para el hospital de la sesión: el personal con membresía (para filtrar por
 * usuario con su nombre y no con el UUID) y los módulos que ya registraron eventos. Solo lectura de CENTRAL.
 */
final class AuditFilterCatalog
{
    private const ACTORS = "SELECT DISTINCT u.user_id, u.display_name, u.email FROM central_users u
        JOIN hospital_memberships m ON m.user_id = u.user_id WHERE m.hospital_id = ? ORDER BY u.display_name";
    private const EVENTS = 'SELECT DISTINCT event FROM audit_log WHERE hospital_id = ?';

    public function __construct(private readonly PDO $pdo) {}

    /** @return array{actors: list<array{user_id: string, display_name: string, email: ?string}>, modules: list<string>} */
    public function for(string $hospitalId): array
    {
        $actors = $this->pdo->prepare(self::ACTORS);
        $actors->execute([$hospitalId]);
        $events = $this->pdo->prepare(self::EVENTS);
        $events->execute([$hospitalId]);

        $modules = array_values(array_unique(array_map(
            static fn (string $event): string => explode('.', $event, 2)[0],
            $events->fetchAll(PDO::FETCH_COLUMN),
        )));
        sort($modules);

        return [
            'actors' => array_map(static fn (array $row): array => [
                'user_id' => (string) $row['user_id'],
                'display_name' => (string) $row['display_name'],
                'email' => $row['email'] === null ? null : (string) $row['email'],
            ], $actors->fetchAll(PDO::FETCH_ASSOC)),
            'modules' => $modules,
        ];
    }
}
