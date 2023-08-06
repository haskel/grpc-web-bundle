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
                ->scalarNode('proto_namespace')->end()
                ->scalarNode('response_type_attribute_name')->end()
                ->arrayNode('exception_code_map')->defaultValue([])
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('exception')->end()
                        ->scalarNode('code')->end()
                    ->end()
                ->end()
                ->arrayNode('security')->defaultValue([])
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('success_response_builder')->end()
                            ->scalarNode('failure_response_builder')->end()
                            ->scalarNode('identifier_field')->end()
                            ->scalarNode('password_field')->end()
                            ->scalarNode('jwt_cookie_builder')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
        ;

        return $treeBuilder;
    }
}
