<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

use function array_map;

final class DataSourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('datasource.extension')) {
            return;
        }

        $driverFactories = [];
        foreach ($container->findTaggedServiceIds('datasource.driver.factory') as $serviceId => $tag) {
            $driverFactories[] = $container->getDefinition($serviceId);
        }

        $container->getDefinition('datasource.driver.factory.manager')->replaceArgument(0, $driverFactories);

        $extensions = [];
        foreach ($container->findTaggedServiceIds('datasource.driver.extension') as $serviceId => $tag) {
            $alias = $tag[0]['alias'] ?? $serviceId;

            $extensions[$alias] = $serviceId;
        }

        $extensionsReferences = array_map(static function (string $extensionId): Reference {
            return new Reference($extensionId);
        }, $extensions);
        $container->getDefinition('datasource.extension')->replaceArgument(0, $extensionsReferences);

        $subscribers = [];
        foreach ($container->findTaggedServiceIds('datasource.subscriber') as $serviceId => $tag) {
            $alias = $tag[0]['alias'] ?? $serviceId;

            $subscribers[$alias] = new Reference($serviceId);
        }

        $container->getDefinition('datasource.extension')->replaceArgument(1, $subscribers);

        foreach ($extensions as $driverExtension) {
            $driverType = $container->getDefinition($driverExtension)->getArgument(0);

            $fields = [];
            $fieldTag = 'datasource.driver.' . $driverType . '.field';
            foreach ($container->findTaggedServiceIds($fieldTag) as $serviceId => $tag) {
                $alias = $tag[0]['alias'] ?? $serviceId;

                $fields[$alias] = new Reference($serviceId);
            }

            $container->getDefinition($driverExtension)->replaceArgument(1, $fields);

            $fieldSubscribers = [];
            $fieldSubscriberTag = 'datasource.driver.' . $driverType . '.field.subscriber';
            foreach ($container->findTaggedServiceIds($fieldSubscriberTag) as $serviceId => $tag) {
                $alias = $tag[0]['alias'] ?? $serviceId;

                $fieldSubscribers[$alias] = new Reference($serviceId);
            }

            $container->getDefinition($driverExtension)->replaceArgument(2, $fieldSubscribers);

            $subscribers = [];
            $driverSubscriberTag = 'datasource.driver.' . $driverType . '.subscriber';
            foreach ($container->findTaggedServiceIds($driverSubscriberTag) as $serviceId => $tag) {
                $alias = $tag[0]['alias'] ?? $serviceId;

                $subscribers[$alias] = new Reference($serviceId);
            }

            $container->getDefinition($driverExtension)->replaceArgument(3, $subscribers);
        }
    }
}
