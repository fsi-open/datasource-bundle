<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\Node;

use FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceExtension;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

class DataSourceRouteNode extends Node
{
    public function __construct(
        Node $dataGrid,
        Node $route,
        AbstractExpression $additionalParameters,
        $lineno,
        $tag = null
    ) {
        parent::__construct(
            [
                'datasource' => $dataGrid,
                'route' => $route,
                'additional_parameters' => $additionalParameters
            ],
            [],
            $lineno,
            $tag
        );
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('$this->env->getExtension(\'%s\')->setRoute(', DataSourceExtension::class))
            ->subcompile($this->getNode('datasource'))
            ->raw(', ')
            ->subcompile($this->getNode('route'))
            ->raw(', ')
            ->subcompile($this->getNode('additional_parameters'))
            ->raw(");\n");
        ;
    }
}
