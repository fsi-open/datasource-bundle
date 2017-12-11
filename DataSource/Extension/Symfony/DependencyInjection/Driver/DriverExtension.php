<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection\Driver;

use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use FSi\Component\DataSource\Field\FieldExtensionInterface;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * DependencyInjection extension loads various types of extensions from Symfony's service container.
 */
class DriverExtension implements DriverExtensionInterface
{
    /**
     * @var string
     */
    private $driverType;

    /**
     * @var FieldTypeInterface[]
     */
    private $fieldTypes = [];

    /**
     * @var FieldExtensionInterface[][]
     */
    private $fieldExtensions = [];

    /**
     * @var EventSubscriberInterface[]
     */
    private $eventSubscribers = [];

    /**
     * @param string $driverType
     * @param FieldTypeInterface[] $fieldTypes
     * @param FieldExtensionInterface[] $fieldExtensions
     * @param EventSubscriberInterface[] $eventSubscribers
     */
    public function __construct($driverType, array $fieldTypes, array $fieldExtensions, array $eventSubscribers)
    {
        $this->driverType = $driverType;

        foreach ($fieldTypes as $fieldType) {
            $this->fieldTypes[$fieldType->getType()] = $fieldType;
        }

        foreach ($fieldExtensions as $fieldExtension) {
            foreach ($fieldExtension->getExtendedFieldTypes() as $extendedFieldType) {
                if (!array_key_exists($extendedFieldType, $this->fieldExtensions)) {
                    $this->fieldExtensions[$extendedFieldType] = [];
                }

                $this->fieldExtensions[$extendedFieldType][] = $fieldExtension;
            }
        }

        $this->eventSubscribers = $eventSubscribers;
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
        return array_key_exists($type, $this->fieldTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldType($type)
    {
        if (!array_key_exists($type, $this->fieldTypes)) {
            throw new \InvalidArgumentException(sprintf('The field type "%s" is not registered within the service container.', $type));
        }

        return $this->fieldTypes[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function hasFieldTypeExtensions($type)
    {
        return array_key_exists($type, $this->fieldExtensions);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldTypeExtensions($type)
    {
        if (!array_key_exists($type, $this->fieldExtensions)) {
            return [];
        }

        return $this->fieldExtensions[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function loadSubscribers()
    {
        return $this->eventSubscribers;
    }
}
