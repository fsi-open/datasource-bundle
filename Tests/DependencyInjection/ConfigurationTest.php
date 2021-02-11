<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FSi\Bundle\DataSourceBundle\Tests\DependencyInjection;

use FSi\Bundle\DataSourceBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    public function testDefaultOptions(): void
    {
        $defaults = [
            'yaml_configuration' => [
                'enabled' => true,
                'main_configuration_directory' => null
            ],
            'twig' => [
                'enabled' => true,
                'template' => '@DataSource/datasource.html.twig'
            ]
        ];

        self::assertSame(
            $defaults,
            $this->processor->processConfiguration(new Configuration(), [[]])
        );
    }

    public function testFoldedYamlConfigurationForTrue(): void
    {
        $folded = [
            'yaml_configuration' => [
                'enabled' => true,
                'main_configuration_directory' => null
            ],
            'twig' => [
                'enabled' => true,
                'template' => '@DataSource/datasource.html.twig'
            ]
        ];

        self::assertSame(
            $folded,
            $this->processor->processConfiguration(new Configuration(), [['yaml_configuration' => true]])
        );
    }

    public function testFoldedYamlConfigurationForFalse(): void
    {
        $folded = [
            'yaml_configuration' => [
                'enabled' => false,
                'main_configuration_directory' => null
            ],
            'twig' => [
                'enabled' => true,
                'template' => '@DataSource/datasource.html.twig'
            ]
        ];

        self::assertSame(
            $folded,
            $this->processor->processConfiguration(new Configuration(), [
                ['yaml_configuration' => false]
            ])
        );
    }

    public function testThemesOption(): void
    {
        $config = $this->processor->processConfiguration(new Configuration(), [
            ['twig' => ['template' => '@DataSource/custom_datasource.html.twig']]
        ]);

        self::assertSame(
            [
                'twig' => ['template' => '@DataSource/custom_datasource.html.twig', 'enabled' => true],
                'yaml_configuration' => ['enabled' => true, 'main_configuration_directory' => null]
            ],
            $config
        );
    }

    public function testCustomMainConfigurationFilesPath(): void
    {
        $config = $this->processor->processConfiguration(new Configuration(), [
            [
                'yaml_configuration' => [
                    'main_configuration_directory' => 'a path to main configuration directory'
                ]
            ]
        ]);

        self::assertSame(
            [
                'yaml_configuration' => [
                    'main_configuration_directory' => 'a path to main configuration directory',
                    'enabled' => true
                ],
                'twig' => ['enabled' => true, 'template' => '@DataSource/datasource.html.twig']
            ],
            $config
        );
    }

    protected function setUp(): void
    {
        $this->processor = new Processor();
    }
}
