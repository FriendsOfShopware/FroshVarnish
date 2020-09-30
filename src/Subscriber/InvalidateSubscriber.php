<?php declare(strict_types=1);

namespace Frosh\Varnish\Subscriber;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvalidateSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $configService;

    private LoggerInterface $logger;

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'onWritten'
        ];
    }

    public function __construct(SystemConfigService $configService, LoggerInterface $logger)
    {
        $this->configService = $configService;
        $this->logger = $logger;
    }

    public function onWritten(EntityWrittenContainerEvent $event): void
    {
        $varnishHost = $this->configService->get('FroshVarnish.config.varnishHost');

        $cacheableEntities = CacheIdSubscriber::CACHEABLE_ENTITIES;
        $invalidateTags = [];

        /** @var EntityWrittenEvent $writtenEvent */
        foreach ($event->getEvents() as $writtenEvent) {
            if (in_array($writtenEvent->getEntityName(), $cacheableEntities, true)) {
                foreach ($writtenEvent->getWriteResults() as $result) {
                    $invalidateTags[] = $writtenEvent->getEntityName() . '-' . $result->getPrimaryKey();
                }
            }
        }

        $invalidateTags = array_unique($invalidateTags);

        if (empty($invalidateTags)) {
            return;
        }

        $client = new Client();
        $response = $client->request('BAN', $varnishHost, [
            'headers' => [
                'Shopware-Cache-Invalidates' => implode(';', $invalidateTags)
            ]
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->logger->error(sprintf('Varnish clearing failed with code %s', $response->getStatusCode()), ['tags' => $invalidateTags]);
        }
    }
}
