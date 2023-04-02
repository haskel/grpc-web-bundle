<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('grpc');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('server')->defaultValue('')->end()
            ->end()
        ->end();
        ;

        return $treeBuilder;
    }
}
