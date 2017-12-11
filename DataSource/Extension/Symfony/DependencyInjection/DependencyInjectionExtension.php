<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\DependencyInjection;

use FSi\Component\DataSource\DataSourceExtensionInterface;
use FSi\Component\DataSource\Driver\DriverExtensionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * DependencyInjection extension loads various types of extensions from Symfony's service container.
 */
class DependencyInjectionExtension implements DataSourceExtensionInterface
{
    /**
     * @var DriverExtensionInterface[]
     */
    private $driverExtensions;

    /**
     * @var EventSubscriberInterface[]
     */
    private $eventSubscribers;

    /**
     * @param DriverExtensionInterface[] $driverExtensions
     * @param EventSubscriberInterface[] $eventSubscribers
     */
    public function __construct(array $driverExtensions, array $eventSubscribers)
    {
        $this->driverExtensions = $driverExtensions;
        $this->eventSubscribers = $eventSubscribers;
    }

    /**
     * {@inheritdoc}
     */
    public function loadDriverExtensions()
    {
        return $this->driverExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function loadSubscribers()
    {
        return $this->eventSubscribers;
    }
}
