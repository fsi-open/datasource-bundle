<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DependencyInjection;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Collection\EventSubscriberInterface;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Collection\FieldEventSubscriberInterface;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\DBAL\EventSubscriberInterface;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\ORM;
use FSi\Component\DataSource\Driver\Collection\CollectionAbstractField;
use FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField;
use FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineAbstractField;
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Driver\DriverFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FSIDataSourceExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('datasource.xml');

        $this->registerDrivers($loader);

        if (isset($config['yaml_configuration']) && $config['yaml_configuration']) {
            $loader->load('datasource_yaml_configuration.xml');
            $container->setParameter(
                'datasource.yaml.main_config',
                $config['yaml_configuration']['main_configuration_directory']
            );
        }

        if (isset($config['twig']['enabled']) && $config['twig']['enabled']) {
            $loader->load('twig.xml');
            $container->setParameter('datasource.twig.template', $config['twig']['template']);
        }

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $this->registerForAutoconfiguration($container);
        }
    }

    private function registerDrivers(LoaderInterface $loader): void
    {
        $loader->load('driver/collection.xml');
        $loader->load('driver/doctrine-orm.xml');
        $loader->load('driver/doctrine-dbal.xml');
    }

    private function registerForAutoconfiguration(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(DriverFactoryInterface::class)->addTag('datasource.driver.factory');
        $container->registerForAutoconfiguration(DriverExtensionInterface::class)->addTag('datasource.driver.extension');
        $container->registerForAutoconfiguration(CollectionAbstractField::class)->addTag('datasource.driver.collection.field');
        $container->registerForAutoconfiguration(FieldEventSubscriberInterface::class)
            ->addTag('datasource.driver.collection.field.subscriber')
        ;
        $container->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag('datasource.driver.collection.subscriber')
        ;
        $container->registerForAutoconfiguration(DoctrineAbstractField::class)
            ->addTag('datasource.driver.doctrine-orm.field')
        ;
        $container->registerForAutoconfiguration(ORM\FieldEventSubscriberInterface::class)
            ->addTag('datasource.driver.doctrine-orm.field.subscriber')
        ;
        $container->registerForAutoconfiguration(ORM\EventSubscriberInterface::class)
            ->addTag('datasource.driver.doctrine-orm.subscriber')
        ;
        $container->registerForAutoconfiguration(DBALAbstractField::class)
            ->addTag('datasource.driver.doctrine-dbal.field')
        ;
        $container->registerForAutoconfiguration(DBAL\FieldEventSubscriberInterface::class)
            ->addTag('datasource.driver.doctrine-dbal.field.subscriber')
        ;
        $container->registerForAutoconfiguration(DBAL\EventSubscriberInterface::class)
            ->addTag('datasource.driver.doctrine-dbal.subscriber')
        ;
    }
}
