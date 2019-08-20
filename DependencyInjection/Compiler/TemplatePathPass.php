<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DependencyInjection\Compiler;

use FSi\Bundle\DataSourceBundle\DataSourceBundle;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TemplatePathPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $loaderDefinition = $container->getDefinition('twig.loader.filesystem');
        if (null === $loaderDefinition) {
            return;
        }

        $reflection = new ReflectionClass(DataSourceBundle::class);
        $loaderDefinition->addMethodCall(
            'addPath',
            [dirname($reflection->getFileName()).'/Resources/views']
        );
    }
}
