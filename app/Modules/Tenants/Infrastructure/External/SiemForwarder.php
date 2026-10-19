<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\External;
use Psr\Log\LoggerInterface;
final class SiemForwarder
{
    private LoggerInterface $logger;
    private ?string $siemEndpoint;
    private ?ClientInterface $httpClient;
    public function __construct(
        LoggerInterface $logger,
        ?string $siemEndpoint = null,
        ?ClientInterface $httpClient = null) {
        $this->logger = $logger;
        $this->siemEndpoint = $siemEndpoint;
        $this->httpClient = $httpClient;}
    public function forward(string $eventName, array $payload): void
    {
        $event = [
            'timestamp' => (new \DateTimeImmutable())->format('c'),
            'source' => 'his-tenants-auth',
            'event' => $eventName,
            'payload' => $payload,];
        $this->logger->info('Security event forwarded to SIEM', $event);
        if ($this->siemEndpoint && $this->httpClient) {
            try {
                $this->httpClient->request('POST', $this->siemEndpoint, [
                    'json' => $event,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'HIS-Tenants-Auth/1.0',],
                    'timeout' => 3,]);
            } catch (\Exception) {
                $this->logger->warning('Failed to forward event to SIEM', ['event' => $eventName]);
            }
        }
    }
}
