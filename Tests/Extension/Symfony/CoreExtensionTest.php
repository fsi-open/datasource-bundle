<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\Extension\Symfony;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Core\CoreExtension;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\DataSourceEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CoreExtensionTest extends TestCase
{
    public function testBindParameters(): void
    {
        $extension = new CoreExtension();
        $datasource = $this->createMock(DataSourceInterface::class);
        $data1 = ['key1' => 'value1', 'key2' => 'value2'];
        $data2 = $data1;

        $subscribers = $extension->loadSubscribers();
        $subscriber = array_shift($subscribers);

        $args = new DataSourceEvent\ParametersEventArgs($datasource, $data2);
        $subscriber->preBindParameters($args);
        $data2 = $args->getParameters();
        self::assertEquals($data1, $data2);

        $request = new Request($data2);
        $args = new DataSourceEvent\ParametersEventArgs($datasource, $request);
        $subscriber->preBindParameters($args);
        $request = $args->getParameters();
        self::assertIsArray($request);
        self::assertEquals($data1, $request);
    }
}
