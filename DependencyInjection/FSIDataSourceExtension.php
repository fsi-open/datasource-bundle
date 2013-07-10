<?php

/**
 * (c) Fabryka Stron Internetowych sp. z o.o <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

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

        if(isset($config['twig']['enabled']) && $config['twig']['enabled']) {
            $this->registerTwigConfiguration($config['twig'], $container, $loader);
        }

/*        if(isset($config['extension']['metadata']['enabled']) && $config['extension']['metadata']['enabled']) {
            $this->registerMetadataExtensionConfiguration($config['extension']['metadata'], $container, $loader);
        }*/
    }

/*    public function registerMetadataExtensionConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('datagrid_metadata_extension.xml');

        if (isset($config['cache_service'])) {
            $container->getDefinition('datagrid.metadata.factory')->addArgument(new Reference($config['cache_service']));;
        }
    }*/

    public function registerTwigConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('twig.xml');
        $container->setParameter('datasource.twig.template', $config['template']);
    }
}
