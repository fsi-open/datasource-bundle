<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\EventSubscriber;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use FSi\Component\DataSource\Event\DataSourceEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class ConfigurationBuilder implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    protected $kernel;

    /**
     * @var \FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader
     */
    protected $configurationLoader;

    /**
     * @param KernelInterface $kernel
     * @param \FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\ConfigurationLoader $configurationLoader
     */
    function __construct(KernelInterface $kernel, ConfigurationLoader $configurationLoader)
    {
        $this->kernel = $kernel;
        $this->configurationLoader = $configurationLoader;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(DataSourceEvents::PRE_BIND_PARAMETERS => array('readConfiguration', 1024));
    }

    /**
     * Method called at PreBindParameters event.
     *
     * @param \FSi\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs $event
     */
    public function readConfiguration(DataSourceEvent\ParametersEventArgs $event)
    {
        $dataSource = $event->getDataSource();
        $dataSourceConfiguration = array();
        foreach ($this->kernel->getBundles() as $bundle) {
            if ($this->hasDataSourceConfiguration($bundle->getPath(), $dataSource->getName())) {

                $configuration = $this->getDataSourceConfiguration($bundle, $dataSource->getName());

                if (is_array($configuration)) {
                    $dataSourceConfiguration = $configuration;
                }
            }
        }

        if (count($dataSourceConfiguration)) {
            $this->buildConfiguration($dataSource, $dataSourceConfiguration);
        }
    }

    /**
     * @param string $bundlePath
     * @param string $dataSourceName
     * @return bool
     */
    protected function hasDataSourceConfiguration($bundlePath, $dataSourceName)
    {
        return file_exists(sprintf($bundlePath . '/Resources/config/datasource/%s.yml', $dataSourceName));
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle
     * @param string $dataSourceName
     * @return mixed
     */
    protected function getDataSourceConfiguration(BundleInterface $bundle, $dataSourceName)
    {


        $config = Yaml::parse(sprintf(
            '%s/Resources/config/datasource/%s.yml',
            $bundle->getPath(),
            $dataSourceName
        ));


        if (isset($config['imports']) && $config['imports']) {
            $config = $this->configurationLoader->load($config, $bundle);
        }

        return $config;
    }

    /**
     * @param DataSourceInterface $dataSource
     * @param array $configuration
     */
    protected function buildConfiguration(DataSourceInterface $dataSource, array $configuration)
    {

        foreach ($configuration['fields'] as $name => $field) {
            $type = array_key_exists('type', $field)
                ? $field['type']
                : null;
            $comparison = array_key_exists('comparison', $field)
                ? $field['comparison']
                : null;
            $options = array_key_exists('options', $field)
                ? $field['options']
                : array();

            $dataSource->addField($name, $type, $comparison, $options);
        }
    }
}
