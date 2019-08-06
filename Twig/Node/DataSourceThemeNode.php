<?php

/**
 * (c) FSi Sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\Twig\Node;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

class DataSourceThemeNode extends Node
{
    public function __construct(Node $dataGrid, Node $theme, AbstractExpression $vars, $lineno, $tag = null)
    {
        parent::__construct(['datasource' => $dataGrid, 'theme' => $theme, 'vars' => $vars], [], $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write('$this->env->getExtension(\'FSi\Bundle\DataSourceBundle\Twig\Extension\DataSourceExtension\')->setTheme(')
            ->subcompile($this->getNode('datasource'))
            ->raw(', ')
            ->subcompile($this->getNode('theme'))
            ->raw(', ')
            ->subcompile($this->getNode('vars'))
            ->raw(");\n");
        ;
    }
}
