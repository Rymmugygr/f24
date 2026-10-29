<?php declare(strict_types=1);

namespace App\Controller\Api\V1;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1.0', name: 'api_v1.0_')]
final class MetricsController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'messenger.transport.currency_daily')]
        private readonly TransportInterface $currencyDailyTransport,
        #[Autowire(service: 'messenger.transport.failed')]
        private readonly TransportInterface $failedTransport,
    ) {
    }

    /**
     * Возвращает количество сообщений в очередях RabbitMQ в формате Prometheus
     */
    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(): Response
    {
        $queues = [
            'currency_daily' => $this->currencyDailyTransport,
            'currency_daily_failed' => $this->failedTransport,
        ];

        $lines = [
            '# HELP rabbitmq_queue_messages Number of messages in the RabbitMQ queue',
            '# TYPE rabbitmq_queue_messages gauge',
        ];

        try {
            foreach ($queues as $name => $transport) {
                $lines[] = sprintf('rabbitmq_queue_messages{queue="%s"} %d', $name, self::messageCount($transport));
            }
        } catch (TransportException $e) {
            $this->logger->error($e->getMessage());
            return new Response(
                "Failed to receive queue metrics\n",
                Response::HTTP_SERVICE_UNAVAILABLE,
                ['Content-Type' => 'text/plain; charset=utf-8'],
            );
        }

        return new Response(
            implode("\n", $lines) . "\n",
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; version=0.0.4; charset=utf-8'],
        );
    }

    private static function messageCount(TransportInterface $transport): int
    {
        if (!$transport instanceof MessageCountAwareInterface) {
            return 0;
        }

        return $transport->getMessageCount();
    }
}
