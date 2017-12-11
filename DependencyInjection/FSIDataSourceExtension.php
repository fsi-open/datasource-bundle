<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FSIDataSourceExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('datasource.xml');

        $this->registerDrivers($loader);

        if (isset($config['yaml_configuration']) && $config['yaml_configuration']) {
            $loader->load('datasource_yaml_configuration.xml');
        }

        if(isset($config['twig']['enabled']) && $config['twig']['enabled']) {
            $this->registerTwigConfiguration($config['twig'], $container, $loader);
        }

        if (method_exists($container, 'registerForAutoconfiguration')) {
            $this->registerForAutoconfiguration($container);
        }
    }

    public function registerTwigConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('twig.xml');
        $container->setParameter('datasource.twig.template', $config['template']);
    }

    /**
     * @param $loader
     */
    private function registerDrivers($loader)
    {
        $loader->load('driver/collection.xml');
        /* doctrine driver is loaded for compatibility with fsi/datasource 1.x only */
        if (class_exists('FSi\Component\DataSource\Driver\Doctrine\DoctrineDriver')) {
            $loader->load('driver/doctrine.xml');
        }
        if (class_exists('FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineDriver')) {
            $loader->load('driver/doctrine-orm.xml');
        }
        if (class_exists('FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver')) {
            $loader->load('driver/doctrine-dbal.xml');
        }
    }

    private function registerForAutoconfiguration(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration('FSi\Component\DataSource\Driver\DriverFactoryInterface')
            ->addTag('datasource.driver.factory');
        $container->registerForAutoconfiguration('FSi\Component\DataSource\Driver\DriverExtensionInterface')
            ->addTag('datasource.driver.extension');
        $container->registerForAutoconfiguration('FSi\Component\DataSource\Driver\Collection\CollectionAbstractField')
            ->addTag('datasource.driver.collection.field');
        $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Collection\FieldEventSubscriberInterface')
            ->addTag('datasource.driver.collection.field.subscriber');
        $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Collection\EventSubscriberInterface')
            ->addTag('datasource.driver.collection.subscriber');
        if (class_exists('FSi\Component\DataSource\Driver\Doctrine\DoctrineDriver')) {
            $container->registerForAutoconfiguration('FSi\Component\DataSource\Driver\Doctrine\DoctrineAbstractField')
                ->addTag('datasource.driver.doctrine.field');
            $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\FieldEventSubscriberInterface')
                ->addTag('datasource.driver.doctrine.field.subscriber');
            $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\EventSubscriberInterface')
                ->addTag('datasource.driver.doctrine.subscriber');
        }
        if (class_exists('FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineDriver')) {
            $container->registerForAutoconfiguration('FSi\Component\DataSource\Driver\Doctrine\ORM\DoctrineAbstractField')
                ->addTag('datasource.driver.doctrine-orm.field');
            $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\ORM\FieldEventSubscriberInterface')
                ->addTag('datasource.driver.doctrine-orm.field.subscriber');
            $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\ORM\EventSubscriberInterface')
                ->addTag('datasource.driver.doctrine-orm.subscriber');
        }
        if (class_exists('FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALDriver')) {
            $container->registerForAutoconfiguration('FSi\Component\DataSource\Driver\Doctrine\DBAL\DBALAbstractField')
                ->addTag('datasource.driver.doctrine-dbal.field');
            $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\DBAL\FieldEventSubscriberInterface')
                ->addTag('datasource.driver.doctrine-dbal.field.subscriber');
            $container->registerForAutoconfiguration('FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver\Doctrine\DBAL\EventSubscriberInterface')
                ->addTag('datasource.driver.doctrine-dbal.subscriber');
        }
    }
}
