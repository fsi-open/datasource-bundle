<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DataSource\Extension\Symfony\Form;

use FSi\Component\DataSource\DataSourceAbstractExtension;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Form extension builds Symfony form for given datasource fields.
 *
 * Extension also maintains replacing parameters that came into request into proper form,
 * replacing these parameters into scalars while getting parameters and sets proper
 * options to view.
 */
class FormExtension extends DataSourceAbstractExtension
{
    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(FormFactory $formFactory, TranslatorInterface $translator)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    public function loadDriverExtensions()
    {
        return [
            new Driver\DriverExtension($this->formFactory, $this->translator),
        ];
    }

    public function loadSubscribers()
    {
        return [
            new EventSubscriber\Events(),
        ];
    }
}
