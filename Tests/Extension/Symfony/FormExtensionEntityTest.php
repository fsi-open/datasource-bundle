<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\Extension\Symfony;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Driver\DriverExtension;
use FSi\Bundle\DataSourceBundle\Tests\Fixtures\News;
use FSi\Bundle\DataSourceBundle\Tests\Fixtures\TestManagerRegistry;
use FSi\Bundle\DataSourceBundle\Tests\Fixtures\TestManagerRegistryNew;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\TranslatorInterface;

use function interface_exists;

class FormExtensionEntityTest extends TestCase
{
    public function testEntityField(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $extension = new DriverExtension($formFactory, $translator);
        $field = $this->createMock(FieldTypeInterface::class);
        $datasource = $this->createMock(DataSourceInterface::class);

        $datasource->method('getName')->willReturn('datasource');

        $field->expects(self::atLeastOnce())->method('getName')->willReturn('name');
        $field->method('getDataSource')->willReturn($datasource);
        $field->method('getName')->willReturn('name');
        $field->method('getType')->willReturn('entity');

        $field->method('hasOption')
            ->willReturnCallback(
                function (string $option): bool {
                    return 'form_options' === $option;
                }
            )
        ;

        $field->method('getOption')
            ->willReturnCallback(
                function (string $option) {
                    switch ($option) {
                        case 'form_filter':
                            return true;
                        case 'form_options':
                            return ['class' => News::class];
                    }

                    return null;
                }
            )
        ;

        $extensions = $extension->getFieldTypeExtensions('entity');
        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'value']]];
        // Form extension will remove 'name' => 'value' since this is not valid entity id
        // (since we have no entities at all).
        $parameters2 = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => []]];
        $args = new FieldEvent\ParameterEventArgs($field, $parameters);
        foreach ($extensions as $ext) {
            self::assertInstanceOf(FieldAbstractExtension::class, $ext);
            $ext->preBindParameter($args);
        }
        $parameters = $args->getParameter();
        self::assertEquals($parameters2, $parameters);

        $fieldView = $this->getMockBuilder(FieldViewInterface::class)->setConstructorArgs([$field])->getMock();
        $fieldView->expects(self::atLeastOnce())->method('setAttribute');

        $args = new FieldEvent\ViewEventArgs($field, $fieldView);
        foreach ($extensions as $ext) {
            $ext->postBuildView($args);
        }
    }

    private function getFormFactory(): FormFactoryInterface
    {
        $typeFactory = new ResolvedFormTypeFactory();
        if (true === interface_exists(PersistenceManagerRegistry::class)) {
            $managerRegistry = new TestManagerRegistryNew($this->getEntityManager());
        } else {
            $managerRegistry = new TestManagerRegistry($this->getEntityManager());
        }

        $registry = new FormRegistry(
            [
                new CoreExtension(),
                new CsrfExtension(new CsrfTokenManager()),
                new DoctrineOrmExtension($managerRegistry),
            ],
            $typeFactory
        );

        return new FormFactory($registry);
    }

    private function getEntityManager(): EntityManager
    {
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . '/../../Fixtures'], true, null, null, false);
        $em = EntityManager::create(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $tool = new SchemaTool($em);
        $tool->createSchema([$em->getClassMetadata(News::class)]);

        return $em;
    }
}
