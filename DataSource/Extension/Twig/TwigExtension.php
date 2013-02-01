<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Lukasz Cybula <lukasz@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Twig;

use FSi\Component\DataSource\Event\DataSourceEvent\ViewEventArgs;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use FSi\Component\DataSource\DataSourceAbstractExtension;
use FSi\Component\DataSource\Event\DataSourceEvents;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Twig\Field\TwigFieldExtension;

class TwigExtension extends DataSourceAbstractExtension implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadSubscribers()
    {
        return array($this);
    }

    /**
     * {@inheritdoc}
     */
    public function loadDriverExtensions()
    {
        return array(
            new Driver\DriverExtension(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(DataSourceEvents::POST_BUILD_VIEW => 'postBuildView');
    }

    public function postBuildView(ViewEventArgs $event)
    {
        $view = $event->getView();

    }
}
