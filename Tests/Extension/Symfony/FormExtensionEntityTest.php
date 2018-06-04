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
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Driver\DriverExtension;
use FSi\Bundle\DataSourceBundle\Tests\Fixtures\News;
use FSi\Bundle\DataSourceBundle\Tests\Fixtures\TestManagerRegistry;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Extension\Core\CoreExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\TranslatorInterface;

class FormExtensionEntityTest extends TestCase
{
    public function testEntityField()
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $extension = new DriverExtension($formFactory, $translator);
        $field = $this->createMock(FieldTypeInterface::class);
        $driver = $this->createMock(DriverInterface::class);
        $datasource = $this->createMock(DataSourceInterface::class, [], [$driver]);

        $datasource->expects($this->any())->method('getName')->will($this->returnValue('datasource'));

        $field->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('name'));
        $field->expects($this->any())->method('getDataSource')->will($this->returnValue($datasource));
        $field->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $field->expects($this->any())->method('getType')->will($this->returnValue('entity'));

        $field
            ->expects($this->any())
            ->method('hasOption')
            ->will($this->returnCallback(function (): bool {
                $args = func_get_args();

                return 'form_options' === array_shift($args);
            }))
        ;

        $field
            ->expects($this->any())
            ->method('getOption')
            ->will($this->returnCallback(function () {
                switch (func_get_arg(0)) {
                    case 'form_filter':
                        return true;
                    case 'form_options':
                        return ['class' => News::class];
                }
            }))
        ;

        $extensions = $extension->getFieldTypeExtensions('entity');
        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'value']]];
        //Form extension will remove 'name' => 'value' since this is not valid entity id (since we have no entities at all).
        $parameters2 = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => []]];
        $args = new FieldEvent\ParameterEventArgs($field, $parameters);
        foreach ($extensions as $ext) {
            $this->assertTrue($ext instanceof FieldAbstractExtension);
            $ext->preBindParameter($args);
        }
        $parameters = $args->getParameter();
        $this->assertEquals($parameters2, $parameters);

        $fieldView = $this->createMock(FieldViewInterface::class, [], [$field]);
        $fieldView->expects($this->atLeastOnce())->method('setAttribute');

        $args = new FieldEvent\ViewEventArgs($field, $fieldView);
        foreach ($extensions as $ext) {
            $ext->postBuildView($args);
        }
    }

    private function getFormFactory(): FormFactoryInterface
    {
        if (version_compare(Kernel::VERSION, '3.0.0', '>=')) {
            $tokenManager = new CsrfTokenManager();
        } else {
            $tokenManager = new DefaultCsrfProvider('tests');
        }

        $typeFactory = new ResolvedFormTypeFactory();
        $registry = new FormRegistry(
            [
                new CoreExtension(),
                new CsrfExtension($tokenManager),
                new DoctrineOrmExtension(new TestManagerRegistry($this->getEntityManager())),
            ],
            $typeFactory
        );

        return new FormFactory($registry, $typeFactory);
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
