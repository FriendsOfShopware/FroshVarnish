<?php declare(strict_types=1);

namespace Frosh\Varnish\Subscriber;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Shopware\Core\Framework\Struct\Collection;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Storefront\Framework\Routing\StorefrontResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheIdSubscriber implements EventSubscriberInterface
{
    /**
     * Only these entities will be the page cache cleared
     */
    public const CACHEABLE_ENTITIES = [
        ProductEntity::class => 'product',
        ProductManufacturerEntity::class => 'product_manufacturer',
        CategoryEntity::class => 'category',
        MediaEntity::class => 'media',
        CmsPageEntity::class => 'cms_page',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSendResponseEvent::class => 'onBeforeResponseSend'
        ];
    }

    public function onBeforeResponseSend(BeforeSendResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (! $response instanceof StorefrontResponse) {
            return;
        }

        if (!isset($response->getData()['page'])) {
            return;
        }

        $tags = array_unique($this->findTags($response->getData()['page']));

        $event->getResponse()->headers->set('Shopware-Cache-Id', ';' . implode(';', $tags) . ';');
    }

    private function findTags(Struct $struct): array
    {
        $items = [];

        foreach ($struct->jsonSerialize() as $item) {
            if ($item instanceof Collection) {
                foreach ($item->getElements() as $element) {
                    $items = [... $items, ... $this->findTags($element)];
                }
            } else {
                foreach (self::CACHEABLE_ENTITIES as $cacheableEntity => $_) {
                    if ($item instanceof $cacheableEntity) {
                        $items[] = $item->get('_entityName') . '-' . $item->getUniqueIdentifier();
                        $items = [... $items, ... $this->findTags($item)];
                    }
                }
            }
        }

        return $items;
    }
}
