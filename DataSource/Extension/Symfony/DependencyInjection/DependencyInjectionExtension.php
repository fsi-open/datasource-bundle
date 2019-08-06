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

    public function __construct(array $driverExtensions, array $eventSubscribers)
    {
        $this->driverExtensions = $driverExtensions;
        $this->eventSubscribers = $eventSubscribers;
    }

    public function loadDriverExtensions()
    {
        return $this->driverExtensions;
    }

    public function loadSubscribers()
    {
        return $this->eventSubscribers;
    }
}
