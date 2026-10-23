<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
/** Puerto opcional para consultar filtraciones de contraseñas (p. ej. HaveIBeenPwned). */
interface CompromisedPasswordChecker
{
    public function isCompromised(string $rawPassword): bool;}
