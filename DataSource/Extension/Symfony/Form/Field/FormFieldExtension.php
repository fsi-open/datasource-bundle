<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field;

use FSi\Component\DataSource\Field\FieldAbstractExtension;
use FSi\Component\DataSource\Field\FieldTypeInterface;
use FSi\Component\DataSource\DataSourceInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use FSi\Component\DataSource\Event\FieldEvents;
use FSi\Component\DataSource\Event\FieldEvent;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Fields extension.
 */
class FormFieldExtension extends FieldAbstractExtension
{
    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $forms = array();

    /**
     * Original values of input parameters for each supported field.
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FieldEvents::PRE_BIND_PARAMETER => array('preBindParameter'),
            FieldEvents::POST_BUILD_VIEW => array('postBuildView'),
            FieldEvents::POST_GET_PARAMETER => array('preGetParameter'),
        );
    }

    public function __construct(FormFactory $formFactory, TranslatorInterface $translator)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedFieldTypes()
    {
        return array('text', 'number', 'date', 'time', 'datetime', 'entity', 'boolean');
    }

    /**
     * {@inheritdoc}
     */
    public function initOptions(FieldTypeInterface $field)
    {
        $field->getOptionsResolver()
            ->setDefaults(array(
                'form_filter' => true,
                'form_options' => array(),
                'form_from_options' => array(),
                'form_to_options' =>array()
            ))
            ->setDefined(array(
                'form_type',
                'form_order'
            ))
            ->setAllowedTypes('form_filter', 'bool')
            ->setAllowedTypes('form_options', 'array')
            ->setAllowedTypes('form_from_options', 'array')
            ->setAllowedTypes('form_to_options', 'array')
            ->setAllowedTypes('form_order', 'integer')
            ->setAllowedTypes('form_type', 'string')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function postBuildView(FieldEvent\ViewEventArgs $event)
    {
        $field = $event->getField();
        $view = $event->getView();

        if ($form = $this->getForm($field)) {
            $view->setAttribute('form', $form->createView());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preBindParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $form = $this->getForm($field);
        if ($form === null) {
            return;
        }

        $field_oid = spl_object_hash($field);
        $parameter = $event->getParameter();

        if ($form->isSubmitted()) {
            $form = $this->getForm($field, true);
        }

        $datasourceName = $field->getDataSource() ? $field->getDataSource()->getName() : null;

        if (empty($datasourceName)) {
            return;
        }

        if ($this->hasParameterValue($parameter, $field)) {
            $this->parameters[$field_oid] = $this->getParameterValue($parameter, $field);

            $fieldForm = $form->get(DataSourceInterface::PARAMETER_FIELDS)->get($field->getName());
            $fieldForm->submit($this->parameters[$field_oid]);
            $data = $fieldForm->getData();

            if ($data !== null) {
                $this->setParameterValue($parameter, $field, $data);
            } else {
                $this->clearParameterValue($parameter, $field);
            }

            $event->setParameter($parameter);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preGetParameter(FieldEvent\ParameterEventArgs $event)
    {
        $field = $event->getField();
        $field_oid = spl_object_hash($field);

        if (isset($this->parameters[$field_oid])) {
            $parameters = array();
            $this->setParameterValue($parameters, $field, $this->parameters[$field_oid]);
            $event->setParameter($parameters);
        }
    }

    /**
     * Builds form.
     *
     * @param FieldTypeInterface $field
     * @param bool $force
     * @return FormInterface|null
     */
    protected function getForm(FieldTypeInterface $field, $force = false)
    {
        $datasource = $field->getDataSource();

        if ($datasource === null) {
            return null;
        }

        if (!$field->getOption('form_filter')) {
            return null;
        }

        $field_oid = spl_object_hash($field);

        if (isset($this->forms[$field_oid]) && !$force) {
            return $this->forms[$field_oid];
        }

        $options = $field->getOption('form_options');
        $options = array_merge($options, array('required' => false, 'auto_initialize' => false));

        $form = $this->formFactory->createNamed(
            $datasource->getName(),
            $this->isFqcnFormTypePossible()
                ? 'Symfony\Component\Form\Extension\Core\Type\CollectionType'
                : 'collection',
            null,
            array('csrf_protection' => false)
        );
        $fieldsForm = $this->formFactory->createNamed(
            DataSourceInterface::PARAMETER_FIELDS,
            $this->isFqcnFormTypePossible()
                ? 'Symfony\Component\Form\Extension\Core\Type\FormType'
                : 'form',
            null,
            array('auto_initialize' => false)
        );

        switch ($field->getComparison()) {
            case 'between':
                $this->buildBetweenComparisonForm($fieldsForm, $field, $options);
                break;

            case 'isNull':
                $this->buildIsNullComparisonForm($fieldsForm, $field, $options);
                break;

            default:

                switch ($field->getType()) {
                    case 'boolean':
                        $this->buildBooleanForm($fieldsForm, $field, $options);
                        break;

                    default:
                        $type = $field->hasOption('form_type')
                            ? $field->getOption('form_type')
                            : $this->getFieldFormType($field);

                        $fieldsForm->add($field->getName(), $type, $options);
                }
        }

        $form->add($fieldsForm);
        $this->forms[$field_oid] = $form;

        return $this->forms[$field_oid];
    }

    /**
     * @param FormInterface $form
     * @param FieldTypeInterface $field
     * @param array $options
     */
    protected function buildBetweenComparisonForm(FormInterface $form, FieldTypeInterface $field, $options = array())
    {
        $betweenBuilder = $this->getFormFactory()->createNamedBuilder(
            $field->getName(),
            $this->isFqcnFormTypePossible()
                ? 'FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Type\BetweenType'
                : 'datasource_between',
            null,
            $options
        );

        $fromOptions = $field->getOption('form_from_options');
        $toOptions = $field->getOption('form_to_options');
        $fromOptions = array_merge($options, $fromOptions);
        $toOptions = array_merge($options, $toOptions);
        $type = $this->getFieldFormType($field);

        if ($field->hasOption('form_type')) {
            $type = $field->getOption('form_type');
        }

        $betweenBuilder->add('from', $type, $fromOptions);
        $betweenBuilder->add('to', $type, $toOptions);

        $form->add($betweenBuilder->getForm());
    }

    /**
     * @param FormInterface $form
     * @param FieldTypeInterface $field
     * @param array $options
     */
    protected function buildIsNullComparisonForm(FormInterface $form, FieldTypeInterface $field, $options = array())
    {
        $defaultOptions = array(
            'choices' => array(
                 $this->translator->trans('datasource.form.choices.is_null', array(), 'DataSourceBundle') => 'null',
                 $this->translator->trans('datasource.form.choices.is_not_null', array(), 'DataSourceBundle') => 'no_null'
            ),
        );

        if ($this->isSymfonyForm27()) {
            $defaultOptions['placeholder'] = '';
        } else {
            $defaultOptions['empty_value'] = '';
            $defaultOptions['choices'] = array_flip($defaultOptions['choices']);
            if (isset($options['choices'])) {
                $options['choices'] = array_merge(
                    $defaultOptions['choices'],
                    array_intersect_key($options['choices'], $defaultOptions['choices'])
                );
            }
        }

        $options = array_merge($defaultOptions, $options);
        $form->add(
            $field->getName(),
            $this->isFqcnFormTypePossible()
                ? 'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
                : 'choice',
            $options
        );
    }

    /**
     * @param FormInterface $form
     * @param FieldTypeInterface $field
     * @param array $options
     */
    protected function buildBooleanForm(FormInterface $form, FieldTypeInterface $field, $options = array())
    {
        $defaultOptions = array(
            'choices' => array(
                $this->translator->trans('datasource.form.choices.yes', array(), 'DataSourceBundle') => '1',
                $this->translator->trans('datasource.form.choices.no', array(), 'DataSourceBundle') => '0',
            ),
        );

        if ($this->isSymfonyForm27()) {
            $defaultOptions['placeholder'] = '';
        } else {
            $defaultOptions['empty_value'] = '';
            $defaultOptions['choices'] = array_flip($defaultOptions['choices']);
            if (isset($options['choices'])) {
                $options['choices'] = array_intersect_key($options['choices'], $defaultOptions['choices']);
            }
        }

        $options = array_merge($defaultOptions, $options);
        $form->add(
            $field->getName(),
            $this->isFqcnFormTypePossible()
                ? 'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
                : 'choice',
            $options
        );
    }

    /**
     * @return FormFactory
     */
    protected function getFormFactory()
    {
        return $this->formFactory;
    }

    private function getFieldFormType(FieldTypeInterface $field)
    {
        if (!$this->isFqcnFormTypePossible()) {
            return $field->getType();
        }

        switch ($field->getType()) {
            case 'text':
                return 'Symfony\Component\Form\Extension\Core\Type\TextType';
            case 'number':
                return 'Symfony\Component\Form\Extension\Core\Type\NumberType';
            case 'date':
                return 'Symfony\Component\Form\Extension\Core\Type\DateType';
            case 'time':
                return 'Symfony\Component\Form\Extension\Core\Type\TimeType';
            case 'datetime':
                return 'Symfony\Component\Form\Extension\Core\Type\DateTimeType';
            case 'entity':
                return 'Symfony\Bridge\Doctrine\Form\Type\EntityType';
            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unsupported field type "%s"',
                    $field->getType()
                ));
        }
    }

    /**
     * @return bool
     */
    private function isFqcnFormTypePossible()
    {
        return class_exists('Symfony\Component\Form\Extension\Core\Type\RangeType');
    }

    /**
     * @param array $array
     * @param FieldTypeInterface $field
     * @return bool
     */
    private function hasParameterValue(array $array, FieldTypeInterface $field)
    {
        return isset(
            $array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()]
        );
    }

    /**
     * @param array $array
     * @param FieldTypeInterface $field
     * @return mixed
     */
    private function getParameterValue(array $array, FieldTypeInterface $field)
    {
        return $array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()];
    }

    /**
     * @param array &$array
     * @param FieldTypeInterface $field
     * @param mixed $value
     */
    private function setParameterValue(array &$array, FieldTypeInterface $field, $value)
    {
        $array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()] = $value;
    }

    /**
     * @param array &$array
     * @param FieldTypeInterface $field
     */
    private function clearParameterValue(array &$array, FieldTypeInterface $field)
    {
        unset($array[$field->getDataSource()->getName()][DataSourceInterface::PARAMETER_FIELDS][$field->getName()]);
    }

    /**
     * @return bool
     */
    private function isSymfonyForm27()
    {
        return method_exists('Symfony\Component\Form\FormTypeInterface', 'configureOptions');
    }
}
