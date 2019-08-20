<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Core;

use FSi\Component\DataSource\DataSourceAbstractExtension;

class CoreExtension extends DataSourceAbstractExtension
{
    public function loadSubscribers()
    {
        return [
            new EventSubscriber\BindParameters(),
        ];
    }
}
