<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Tests\Fixtures\Form\Extension\TestCore\Type;

use Symfony\Component\Form\Extension\Core\Type\FormType as BaseFormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FormType extends BaseFormType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['type'] = $form->getConfig()->getType()->getName();
    }
}
