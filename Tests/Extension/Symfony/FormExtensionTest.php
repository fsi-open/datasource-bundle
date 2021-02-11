<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\Extension\Symfony;

use DateTimeImmutable;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Driver\DriverExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\EventSubscriber\Events;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Extension\DatasourceExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field\FormFieldExtension;
use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType;
use FSi\Bundle\DataSourceBundle\Tests\Fixtures\Form as TestForm;
use FSi\Component\DataSource\DataSourceInterface;
use FSi\Component\DataSource\DataSourceViewInterface;
use FSi\Component\DataSource\Driver\Collection\Extension\Core\Field\Boolean;
use FSi\Component\DataSource\Driver\DriverInterface;
use FSi\Component\DataSource\Event\DataSourceEvent\ViewEventArgs;
use FSi\Component\DataSource\Event\FieldEvent;
use FSi\Component\DataSource\Exception\DataSourceException;
use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\Field\FieldView;
use FSi\Component\DataSource\Field\FieldViewInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Form;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Translation\TranslatorInterface;

class FormExtensionTest extends TestCase
{
    public static function typesProvider(): array
    {
        return [
            ['text'],
            ['number'],
            ['date'],
            ['time'],
            ['datetime']
        ];
    }

    /**
     * Provides field types, comparison types and expected form input types.
     *
     * @return array
     */
    public static function fieldTypesProvider(): array
    {
        return [
            ['text', 'isNull', 'choice'],
            ['text', 'eq', 'text'],
            ['number', 'isNull', 'choice'],
            ['number', 'eq', 'number'],
            ['datetime', 'isNull', 'choice'],
            ['datetime', 'eq', 'datetime'],
            ['datetime', 'between', 'datasource_between'],
            ['time', 'isNull', 'choice'],
            ['time', 'eq', 'time'],
            ['date', 'isNull', 'choice'],
            ['date', 'eq', 'date']
        ];
    }

    /**
     * Checks creation of DriverExtension.
     */
    public function testCreateDriverExtension(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);

        $driver = new DriverExtension($formFactory, $translator);
        // Without an assertion the test would be marked as risky
        self::assertNotNull($driver);
    }

    /**
     * Tests if driver extension has all needed fields.
     */
    public function testDriverExtension(): void
    {
        $this->expectException(DataSourceException::class);

        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $extension = new DriverExtension($formFactory, $translator);

        self::assertTrue($extension->hasFieldTypeExtensions('text'));
        self::assertTrue($extension->hasFieldTypeExtensions('number'));
        self::assertTrue($extension->hasFieldTypeExtensions('entity'));
        self::assertTrue($extension->hasFieldTypeExtensions('date'));
        self::assertTrue($extension->hasFieldTypeExtensions('time'));
        self::assertTrue($extension->hasFieldTypeExtensions('datetime'));
        self::assertFalse($extension->hasFieldTypeExtensions('wrong'));

        $extension->getFieldTypeExtensions('text');
        $extension->getFieldTypeExtensions('number');
        $extension->getFieldTypeExtensions('entity');
        $extension->getFieldTypeExtensions('date');
        $extension->getFieldTypeExtensions('time');
        $extension->getFieldTypeExtensions('datetime');
        $extension->getFieldTypeExtensions('wrong');
    }

    public function testFormOrder(): void
    {
        $datasource = $this->createMock(DataSourceInterface::class);
        $view = $this->createMock(DataSourceViewInterface::class);

        $fields = [];
        $fieldViews = [];
        for ($i = 0; $i < 15; $i++) {
            $field = $this->createMock(FieldTypeInterface::class);
            $fieldView = $this->createMock(FieldViewInterface::class);

            unset($order);
            if ($i < 5) {
                $order = -4 + $i;
            } elseif ($i > 10) {
                $order = $i - 10;
            }

            $field->method('getName')->willReturn('field' . $i);
            $field->method('hasOption')->willReturn(isset($order));

            if (isset($order)) {
                $field->method('getOption')->willReturn($order);
            }

            $fieldView->method('getName')->willReturn('field' . $i);
            $fields['field' . $i] = $field;
            $fieldViews['field' . $i] = $fieldView;
            if (isset($order)) {
                $names['field' . $i] = $order;
            } else {
                $names['field' . $i] = null;
            }
        }

        $datasource
            ->method('getField')
            ->willReturnCallback(
                function ($field) use ($fields) {
                    return $fields[$field];
                }
            )
        ;

        $view->method('getFields')->willReturn($fieldViews);
        $view
            ->expects(self::once())
            ->method('setFields')
            ->willReturnCallback(
                function (array $fields) {
                    $names = [];
                    foreach ($fields as $field) {
                        $names[] = $field->getName();
                    }

                    $this->assertSame(
                        [
                            'field0',
                            'field1',
                            'field2',
                            'field3',
                            'field5',
                            'field6',
                            'field7',
                            'field8',
                            'field9',
                            'field10',
                            'field4',
                            'field11',
                            'field12',
                            'field13',
                            'field14'
                        ],
                        $names
                    );
                }
            )
        ;

        $event = new ViewEventArgs($datasource, $view);
        $subscriber = new Events();
        $subscriber->postBuildView($event);
    }

    /**
     * @dataProvider typesProvider()
     */
    public function testFields(string $type): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->createMock(TranslatorInterface::class);
        $extension = new DriverExtension($formFactory, $translator);
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');

        $field = $this->createMock(FieldTypeInterface::class);
        $field->expects(self::atLeastOnce())->method('getName')->willReturn('name');
        $field->method('getDataSource')->willReturn($datasource);
        $field->method('getType')->willReturn($type);
        $field->method('hasOption')->willReturn(false);

        $field
            ->method('getOption')
            ->willReturnCallback(
                function (string $option) use ($type) {
                    switch ($option) {
                        case 'form_filter':
                            return true;
                        case 'form_from_options':
                        case 'form_to_options':
                            return [];
                        case 'form_options':
                            return true === in_array($type, ['date', 'datetime'], true)
                                // By default the year range for the date select widget
                                // is 5 years into past.
                                ? ['years' => range(2012, (int) date('Y'))]
                                : [];
                    }

                    return null;
                }
            )
        ;

        $extensions = $extension->getFieldTypeExtensions($type);
        if ($type === 'datetime') {
            $parameters = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => [
                            'date' => ['year' => 2012, 'month' => 12, 'day' => 12],
                            'time' => ['hour' => 12, 'minute' => 12],
                        ]
                    ]
                ]
            ];
            $parameters2 = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => new DateTimeImmutable('2012-12-12 12:12:00')
                    ]
                ]
            ];
        } elseif ($type === 'time') {
            $parameters = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => ['hour' => 12, 'minute' => 12]
                    ]
                ]
            ];
            $parameters2 = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => new DateTimeImmutable(date('Y-m-d', 0) . ' 12:12:00')
                    ]
                ]
            ];
        } elseif ($type === 'date') {
            $parameters = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => ['year' => 2012, 'month' => 12, 'day' => 12]
                    ]
                ]
            ];
            $parameters2 = [
                'datasource' => [
                    DataSourceInterface::PARAMETER_FIELDS => [
                        'name' => new DateTimeImmutable('2012-12-12')
                    ]
                ]
            ];
        } elseif ($type === 'number') {
            $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 123]]];
            $parameters2 = $parameters;
        } else {
            $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'value']]];
            $parameters2 = $parameters;
        }

        $args = new FieldEvent\ParameterEventArgs($field, $parameters);
        foreach ($extensions as $ext) {
            self::assertInstanceOf(FormFieldExtension::class, $ext);
            $ext->preBindParameter($args);
        }

        self::assertEquals($parameters2, $args->getParameter());
        $fieldView = $this->getMockBuilder(FieldViewInterface::class)
            ->setConstructorArgs([$field])
            ->getMock()
        ;

        $fieldView
            ->expects(self::atLeastOnce())
            ->method('setAttribute')
            ->willReturnCallback(
                function (string $attribute, $value): void {
                    if ($attribute === 'form') {
                        self::assertInstanceOf(FormView::class, $value);
                    }
                }
            )
        ;

        $args = new FieldEvent\ViewEventArgs($field, $fieldView);
        foreach ($extensions as $ext) {
            self::assertInstanceOf(FormFieldExtension::class, $ext);
            $ext->postBuildView($args);
        }
    }

    /**
     * @dataProvider fieldTypesProvider
     */
    public function testFormFields(string $type, string $comparison, $expected): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $extension = new DriverExtension($formFactory, $translator);
        $driver = $this->createMock(DriverInterface::class);
        $datasource = $this->createMock(DataSourceInterface::class);
        $datasource->method('getName')->willReturn('datasource');

        $field = $this->createMock(FieldTypeInterface::class);
        $field->expects(self::atLeastOnce())->method('getName')->willReturn('name');
        $field->method('getDataSource')->willReturn($datasource);
        $field->method('getType')->willReturn($type);
        $field->method('hasOption')->willReturn(false);
        $field->method('getComparison')->willReturn($comparison);
        $field
            ->method('getOption')
            ->willReturnCallback(
                function (string $option) {
                    switch ($option) {
                        case 'form_filter':
                            return true;

                        case 'form_type':
                            return null;

                        case 'form_from_options':
                        case 'form_to_options':
                        case 'form_options':
                            return [];
                    }

                    return null;
                }
            )
        ;
        $extensions = $extension->getFieldTypeExtensions($type);

        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]];
        $args = new FieldEvent\ParameterEventArgs($field, $parameters);

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        foreach ($extensions as $ext) {
            self::assertInstanceOf(FormFieldExtension::class, $ext);
            $ext->preBindParameter($args);
            $ext->postBuildView($viewEventArgs);
        }

        $form = $viewEventArgs->getView()->getAttribute('form');

        self::assertEquals($expected, $form['fields']['name']->vars['block_prefixes'][1]);

        if ($comparison === 'isNull') {
            self::assertEquals(
                'is_null_translated',
                $form['fields']['name']->vars['choices'][0]->label
            );
            self::assertEquals(
                'is_not_null_translated',
                $form['fields']['name']->vars['choices'][1]->label
            );
        }
    }

    public function testBuildBooleanFormWhenOptionsProvided(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $formFieldExtension = new FormFieldExtension($formFactory, $translator);
        $driver = $this->createMock(DriverInterface::class);
        $datasource = $this->createMock(DataSourceInterface::class);

        $field = $this->createMock(Boolean::class);
        $field->expects(self::atLeastOnce())->method('getName')->willReturn('name');
        $field->expects(self::atLeastOnce())->method('getDataSource')->willReturn($datasource);
        $field->expects(self::atLeastOnce())->method('getType')->willReturn('boolean');
        $field->expects(self::atLeastOnce())
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'form_filter':
                            return true;
                        case 'form_options':
                            return ['choices' => ['tak' => '1', 'nie' => '0']];
                    }
                }
            );

        $parameters = ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]];
        $args = new FieldEvent\ParameterEventArgs($field, $parameters);

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        $formFieldExtension->preBindParameter($args);
        $formFieldExtension->postBuildView($viewEventArgs);

        $form = $viewEventArgs->getView()->getAttribute('form');
        $choices = $form['fields']['name']->vars['choices'];
        self::assertEquals($choices[0]->value, '1');
        self::assertEquals($choices[0]->label, 'tak');
        self::assertEquals($choices[1]->value, '0');
        self::assertEquals($choices[1]->label, 'nie');
    }

    public function testBuildBooleanFormWhenOptionsNotProvided(): void
    {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $formFieldExtension = new FormFieldExtension($formFactory, $translator);
        $datasource = $this->createMock(DataSourceInterface::class);

        $field = $this->createMock(Boolean::class);
        $field->expects(self::atLeastOnce())->method('getName')->willReturn('name');
        $field->expects(self::atLeastOnce())->method('getDataSource')->willReturn($datasource);
        $field->expects(self::atLeastOnce())->method('getType')->willReturn('boolean');
        $field->expects(self::atLeastOnce())
            ->method('getOption')
            ->willReturnCallback(
                function ($option) {
                    switch ($option) {
                        case 'form_filter':
                            return true;
                        case 'form_options':
                            return [];
                    }
                }
            );

        $args = new FieldEvent\ParameterEventArgs(
            $field,
            ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]]
        );

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        $formFieldExtension->preBindParameter($args);
        $formFieldExtension->postBuildView($viewEventArgs);

        $form = $viewEventArgs->getView()->getAttribute('form');
        $choices = $form['fields']['name']->vars['choices'];
        self::assertEquals($choices[0]->value, '1');
        self::assertEquals($choices[0]->label, 'yes_translated');
        self::assertEquals($choices[1]->value, '0');
        self::assertEquals($choices[1]->label, 'no_translated');
    }

    /**
     * @dataProvider getDatasourceFieldTypes
     */
    public function testCreateDataSourceFieldWithCustomFormType(
        string $dataSourceFieldType,
        string $comparison = null
    ): void {
        $formFactory = $this->getFormFactory();
        $translator = $this->getTranslator();
        $formFieldExtension = new FormFieldExtension($formFactory, $translator);
        $field = $this->createMock(FieldTypeInterface::class);
        $datasource = $this->createMock(DataSourceInterface::class);

        $field->expects(self::atLeastOnce())->method('getName')->willReturn('name');
        $field->expects(self::atLeastOnce())->method('getDataSource')->willReturn($datasource);
        $field->expects(self::atLeastOnce())->method('getType')->willReturn($dataSourceFieldType);
        $field->expects(self::atLeastOnce())->method('getComparison')->willReturn($comparison);

        $options = [
            'form_filter' => true,
            'form_options' => [],
            'form_type' => HiddenType::class
        ];

        $field->expects(self::atLeastOnce())
            ->method('hasOption')
            ->willReturnCallback(
                function ($option) use ($options) {
                    return isset($options[$option]);
                }
            );

        $field->expects(self::atLeastOnce())
            ->method('getOption')
            ->willReturnCallback(
                function ($option) use ($options) {
                    return $options[$option];
                }
            );

        $args = new FieldEvent\ParameterEventArgs(
            $field,
            ['datasource' => [DataSourceInterface::PARAMETER_FIELDS => ['name' => 'null']]]
        );

        $view = new FieldView($field);
        $viewEventArgs = new FieldEvent\ViewEventArgs($field, $view);

        $formFieldExtension->preBindParameter($args);
        $formFieldExtension->postBuildView($viewEventArgs);

        $form = $viewEventArgs->getView()->getAttribute('form');
        self::assertEquals('hidden', $form['fields']['name']->vars['block_prefixes'][1]);
    }

    public function getDatasourceFieldTypes(): array
    {
        return [
            [
                'text',  //datasource field type
                'isNull', //comparison
            ],
            ['text'],
            ['number'],
            ['date'],
            ['time'],
            ['datetime'],
            ['boolean']
        ];
    }

    private function getFormFactory(): FormFactoryInterface
    {
        $typeFactory = new Form\ResolvedFormTypeFactory();
        $typeFactory->createResolvedType(new BetweenType(), []);

        $registry = new Form\FormRegistry(
            [
                new TestForm\Extension\TestCore\TestCoreExtension(),
                new Form\Extension\Core\CoreExtension(),
                new Form\Extension\Csrf\CsrfExtension(new CsrfTokenManager()),
                new DatasourceExtension()
            ],
            $typeFactory
        );

        return new Form\FormFactory($registry);
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(
                function ($id, array $params, $translation_domain): string {
                    if ($translation_domain !== 'DataSourceBundle') {
                        throw new RuntimeException(sprintf('Unknown translation domain %s', $translation_domain));
                    }

                    switch ($id) {
                        case 'datasource.form.choices.is_null':
                            return 'is_null_translated';
                        case 'datasource.form.choices.is_not_null':
                            return 'is_not_null_translated';
                        case 'datasource.form.choices.yes':
                            return 'yes_translated';
                        case 'datasource.form.choices.no':
                            return 'no_translated';
                        default:
                            throw new RuntimeException(sprintf('Unknown translation id %s', $id));
                    }
                }
            );

        return $translator;
    }
}
