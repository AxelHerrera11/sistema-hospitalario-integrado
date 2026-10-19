<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Audit;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Psr\Log\LoggerInterface;
final class EventPublisher
{
    private PsrProducer $producer;
    private PsrContext $context;
    private LoggerInterface $logger;
    public function __construct(
        ProducerInterface $producer,
        PsrContext $context,
        LoggerInterface $logger
    ) {
        $this->producer = $producer;
        $this->context = $context;
        $this->logger = $logger;
    }
    public function publish(DomainEvent $event): void
    {
        try {
            $message = $this->context->createMessage(json_encode([
                'event_id' => $event->eventId(),
                'event_name' => $event->eventName(),
                'payload' => $event->payload(),
                'occurred_at' => $event->occurredAt()->format('c'),
            ]));
            $message->setRoutingKey('tenants.auth.' . strtolower($event->eventName()));
            $this->producer->send($message);
        } catch (\Exception $e) {
            $this->logger->error('Error publicando evento: ' . $e->getMessage(), [
                'event' => $event->eventName(),
                'event_id' => $event->eventId(),
            ]);
        }
    }
}
