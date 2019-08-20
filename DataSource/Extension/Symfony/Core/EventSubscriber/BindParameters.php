<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Component\DataSource\Event\DataSourceEvent;
use Symfony\Component\HttpFoundation\Request;

class BindParameters implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [DataSourceEvents::PRE_BIND_PARAMETERS => ['preBindParameters', 1024]];
    }

    public function preBindParameters(DataSourceEvent\ParametersEventArgs $event)
    {
        $parameters = $event->getParameters();
        if ($parameters instanceof Request) {
            $event->setParameters($parameters->query->all());
        }
    }
}
