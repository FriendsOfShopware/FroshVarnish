<?php declare(strict_types=1);

namespace Frosh\Varnish\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EnableAjaxCsrfCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $csrf = $container->getParameter('storefront.csrf');
        $csrf['mode'] = 'ajax';

        $container->setParameter('storefront.csrf', $csrf);
        $container->setParameter('storefront.csrf.mode', 'ajax');
    }
}
