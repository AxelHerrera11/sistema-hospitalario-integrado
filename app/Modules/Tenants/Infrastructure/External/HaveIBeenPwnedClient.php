<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\External;
use App\Modules\Tenants\Domain\Services\CompromisedPasswordChecker;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
/** Consulta k-anonimato de Pwned Passwords: solo viajan los 5 primeros caracteres del SHA-1. */
final class HaveIBeenPwnedClient implements CompromisedPasswordChecker
{
    private ClientInterface $httpClient;
    private string $baseUrl = 'https://api.pwnedpasswords.com/range/';
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;}
    public function isCompromised(string $rawPassword): bool
    {
        $hash = strtoupper(sha1($rawPassword));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . $prefix, [
                'headers' => [
                    'User-Agent' => 'HIS-Tenants-Auth/1.0',
                    'Add-Padding' => 'true',],
                'timeout' => 5,]);
        } catch (GuzzleException) {
            // Si el servicio no responde no se bloquea el alta; el resto de la política sí se aplica.
            return false;}
        foreach (explode("\n", (string) $response->getBody()) as $line) {
            $parts = explode(':', trim($line));
            if (count($parts) === 2 && strtoupper($parts[0]) === $suffix && (int) $parts[1] > 0) {
                return true;}}
        return false;}}
