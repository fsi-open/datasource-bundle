<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Configuration\EventSubscriber;

use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\DataSourceEvent\ParametersEventArgs;
use FSi\Component\DataSource\Event\DataSourceEvents;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Parser;

class ConfigurationBuilder implements EventSubscriberInterface
{
    private const BUNDLE_CONFIG_PATH = '%s/Resources/config/datasource/%s.yml';
    private const MAIN_CONFIG_DIRECTORY = 'datasource.yaml.main_config';

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Parser
     */
    private $yamlParser;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->yamlParser = new Parser();
    }

    public static function getSubscribedEvents()
    {
        return [DataSourceEvents::PRE_BIND_PARAMETERS => ['readConfiguration', 1024]];
    }

    public function readConfiguration(ParametersEventArgs $event)
    {
        $dataSource = $event->getDataSource();
        $mainConfiguration = $this->getMainConfiguration($dataSource->getName());
        if (null !== $mainConfiguration) {
            $this->buildConfiguration($dataSource, $mainConfiguration);
        } else {
            $this->buildConfigurationFromRegisteredBundles($dataSource);
        }
    }

    private function getMainConfiguration(string $dataSourceName): ?array
    {
        $directory = $this->kernel->getContainer()->getParameter(self::MAIN_CONFIG_DIRECTORY);
        if (null === $directory) {
            return null;
        }

        if (false === is_dir($directory)) {
            throw new RuntimeException(sprintf('"%s" is not a directory!', $directory));
        }

        $configurationFile = sprintf('%s/%s.yml', rtrim($directory, '/'), $dataSourceName);
        if (false === file_exists($configurationFile)) {
            return null;
        }

        $configuration = $this->parseYamlFile($configurationFile);
        if (false === is_array($configuration)) {
            return null;
        }

        return $configuration;
    }

    private function buildConfigurationFromRegisteredBundles(DataSourceInterface $dataSource): void
    {
        $dataSourceName = $dataSource->getName();
        $bundles = $this->kernel->getBundles();
        $eligibleBundles = array_filter(
            $bundles,
            function (BundleInterface $bundle) use ($dataSourceName): bool {
                return file_exists(sprintf(self::BUNDLE_CONFIG_PATH, $bundle->getPath(), $dataSourceName));
            }
        );

        // The idea here is that the last found configuration should be used
        $configuration = $this->findLastBundleConfiguration($dataSourceName, $eligibleBundles);
        if (0 !== count($configuration)) {
            $this->buildConfiguration($dataSource, $configuration);
        }
    }

    private function findLastBundleConfiguration(string $dataSourceName, array $eligibleBundles): array
    {
        return array_reduce(
            $eligibleBundles,
            function (array $configuration, BundleInterface $bundle) use ($dataSourceName): array {
                $overridingConfiguration = $this->parseYamlFile(
                    sprintf(self::BUNDLE_CONFIG_PATH, $bundle->getPath(), $dataSourceName)
                );
                if (true === is_array($overridingConfiguration)) {
                    $configuration = $overridingConfiguration;
                }

                return $configuration;
            },
            []
        );
    }

    private function buildConfiguration(DataSourceInterface $dataSource, array $configuration): void
    {
        foreach ($configuration['fields'] as $name => $field) {
            $dataSource->addField(
                $name,
                $field['type'] ?? null,
                $field['comparison'] ?? null,
                $field['options'] ?? []
            );
        }
    }

    private function parseYamlFile(string $path)
    {
        return $this->yamlParser->parse(file_get_contents($path));
    }
}
