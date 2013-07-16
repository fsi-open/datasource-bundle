<?php

/*
 * This file is part of the FSi Component package.
 *
 * (c) Lukasz Cybula <lukasz@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\Bundle\DataSourceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('fsi_data_source');
        $rootNode
            ->children()
                ->arrayNode('twig')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->scalarNode('template')->defaultValue('datasource.html.twig')->end()
                    ->end()
                ->end()
/*                ->arrayNode('extension')
                    ->children()
                        ->arrayNode('metadata')
                            ->treatFalseLike(array('enabled' => false))
                            ->treatTrueLike(array('enabled' => true))
                            ->treatNullLike(array('enabled' => true))
                            ->children()
                                ->booleanNode('enabled')->defaultTrue()->end()
                                ->scalarNode('cache_service')->end()
                            ->end()
                        ->end()
                    ->end()*/
            ->end()
        ->end();

        return $tb;
    }
}
