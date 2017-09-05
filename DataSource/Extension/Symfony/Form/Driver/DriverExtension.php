<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Driver;

use FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form\Field;
use FSi\Component\DataSource\Driver\DriverAbstractExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Driver extension for form that loads fields extension.
 */
class DriverExtension extends DriverAbstractExtension
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param FormFactory $formFactory
     */
    public function __construct(FormFactory $formFactory, TranslatorInterface $translator)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedDriverTypes()
    {
        return ['doctrine-orm'];
    }

    /**
     * {@inheritdoc}
     */
    protected function loadFieldTypesExtensions()
    {
        return [new Field\FormFieldExtension($this->formFactory, $this->translator)];
    }
}
