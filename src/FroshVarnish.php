<?php declare(strict_types=1);

namespace Frosh\Varnish;

use Frosh\Varnish\DependencyInjection\EnableAjaxCsrfCompilerPass;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\CachedEntityAggregator;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\CachedEntityReader;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\CachedEntitySearcher;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Cache\CacheWarmer\CacheWarmer;
use Shopware\Storefront\Framework\Command\HttpCacheWarmUpCommand;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

class FroshVarnish extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $container->removeDefinition(CachedEntitySearcher::class);
        $container->removeDefinition(CachedEntityAggregator::class);
        $container->removeDefinition(CachedEntityReader::class);
        $container->removeDefinition(HttpCacheWarmUpCommand::class);
        $container->removeDefinition(CacheWarmer::class);

        $container->addCompilerPass(new EnableAjaxCsrfCompilerPass());

        parent::build($container);
    }

    public function install(InstallContext $installContext): void
    {
        $request = Request::createFromGlobals();

        $this->container->get(SystemConfigService::class)->set('FroshVarnish.config.varnishHost', $request->getSchemeAndHttpHost());
    }
}
