<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\External;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
final class HaveIBeenPwnedClient
{
    private ClientInterface $httpClient;
    private string $apiKey;
    private string $baseUrl = 'https://api.pwnedpasswords.com/range/';
    public function __construct(ClientInterface $httpClient, string $apiKey = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;}
    public function check(string $password): HibpResult
    {
        $hash = strtoupper(hash('sha1', $password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . $prefix, [
                'headers' => [
                    'User-Agent' => 'HIS-Tenants-Auth/1.0',
                    'Accept' => 'text/plain',
                    'Add-Padding' => 'true',
                ] + ($this->apiKey ? ['hibp-api-key' => $this->apiKey] : []),
                'timeout' => 5,]);
            $body = $response->getBody()->getContents();
            $lines = explode("\n", $body);

            foreach ($lines as $line) {
                [$lineSuffix, $count] = explode(':', trim($line));
                if (strtoupper($lineSuffix) === $suffix) {
                    return new HibpResult(true, (int)$count);}}
            return new HibpResult(false, 0);
        } catch (GuzzleException) {
            return new HibpResult(false, 0);
        }
    }
}
