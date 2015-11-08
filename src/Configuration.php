<?php
/**
 * Copyright © 2015, Ambroise Maupate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @file Configuration.php
 * @author Ambroise Maupate
 */
namespace RZ\RoadizCliTools;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('clitools');

        $rootNode
            ->children()
                ->arrayNode('commands')
                    ->children()
                        ->arrayNode('git')
                            ->children()
                                ->scalarNode('path')->isRequired()->defaultValue('git')->end()
                                ->scalarNode('test')->isRequired()->defaultValue('git --version')->end()
                            ->end()
                        ->end()
                        ->arrayNode('mysqldump')
                            ->children()
                                ->scalarNode('path')->isRequired()->defaultValue('mysqldump')->end()
                                ->scalarNode('test')->isRequired()->defaultValue('mysqldump --version')->end()
                            ->end()
                        ->end()
                        ->arrayNode('mysql')
                            ->children()
                                ->scalarNode('path')->isRequired()->defaultValue('mysql')->end()
                                ->scalarNode('test')->isRequired()->defaultValue('mysql --version')->end()
                            ->end()
                        ->end()
                        ->arrayNode('cp')
                            ->children()
                                ->scalarNode('path')->isRequired()->defaultValue('cp')->end()
                                ->scalarNode('test')->isRequired()->defaultValue('whereis cp')->end()
                            ->end()
                        ->end()
                        ->arrayNode('rsync')
                            ->children()
                                ->scalarNode('path')->isRequired()->defaultValue('rsync')->end()
                                ->scalarNode('test')->isRequired()->defaultValue('rsync --version')->end()
                            ->end()
                        ->end()
                        ->arrayNode('composer')
                            ->children()
                                ->scalarNode('path')->isRequired()->defaultValue('composer')->end()
                                ->scalarNode('test')->isRequired()->defaultValue('composer --version')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('db')
                    ->children()
                        ->scalarNode('host')->defaultValue('localhost')->end()
                        ->scalarNode('username')->defaultValue('root')->end()
                        ->scalarNode('password')->isRequired()->end()
                        ->scalarNode('port')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
