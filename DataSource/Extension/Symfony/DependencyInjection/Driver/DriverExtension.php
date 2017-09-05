<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver;

use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DependencyInjection extension loads various types of extensions from Symfony's service container.
 */
class DriverExtension extends DriverAbstractExtension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $driverType;

    /**
     * @var array
     */
    protected $fieldServiceIds;

    /**
     * @var array
     */
    protected $fieldExtensionServiceIds;

    /**
     * @var array
     */
    protected $subscriberServiceIds;

    /**
     * @param ContainerInterface $container
     * @param string $driverType
     * @param array $fieldServiceIds
     * @param array $fieldExtensionServiceIds
     * @param array $subscriberServiceIds
     */
    public function __construct(ContainerInterface $container, $driverType, array $fieldServiceIds, array $fieldExtensionServiceIds, array $subscriberServiceIds)
    {
        $this->container = $container;
        $this->driverType = $driverType;
        $this->fieldServiceIds = $fieldServiceIds;
        $this->fieldExtensionServiceIds = $fieldExtensionServiceIds;
        $this->subscriberServiceIds = $subscriberServiceIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedDriverTypes()
    {
        return [$this->driverType];
    }

    /**
     * {@inheritdoc}
     */
    public function hasFieldType($type)
    {
        return isset($this->fieldServiceIds[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType($type)
    {
        if (!isset($this->fieldServiceIds[$type])) {
            throw new \InvalidArgumentException(sprintf('The field type "%s" is not registered within the service container.', $type));
        }

        return $this->container->get($this->fieldServiceIds[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasFieldTypeExtensions($type)
    {
        foreach ($this->fieldExtensionServiceIds as $extensionName) {
            $extension = $this->container->get($extensionName);
            $types = $extension->getExtendedFieldTypes();
            if (in_array($type, $types)) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldTypeExtensions($type)
    {
        $fieldExtensions = [];

        foreach ($this->fieldExtensionServiceIds as $extensionName) {
            $extension = $this->container->get($extensionName);
            $types = $extension->getExtendedFieldTypes();
            if (in_array($type, $types)) {
                $fieldExtensions[] = $extension;
            }
        }

        return $fieldExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function loadSubscribers()
    {
        $subscribers = [];

        foreach ($this->subscriberServiceIds as $subscriberName) {
            $subscribers[] = $this->container->get($subscriberName);
        }

        return $subscribers;
    }
}
