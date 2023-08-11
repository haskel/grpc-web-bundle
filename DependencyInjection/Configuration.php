<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('grpc_web');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('proto_namespace')->end()
                ->scalarNode('response_type_attribute_name')->end()
            ->end();

        $rootNode
            ->children()
                ->arrayNode('exception_code_map')
                    ->useAttributeAsKey('exception') // Use exception names as keys
                    ->prototype('scalar') // Expect a scalar value for each key
                    ->end()
                ->end()
            ->end();

        $rootNode
            ->children()
                ->arrayNode('security')
                    ->children()
                        ->scalarNode('success_response_builder')->defaultValue(null)->end()
                        ->scalarNode('failure_response_builder')->defaultValue(null)->end()
                        ->scalarNode('sign_in_request_class')->end()
                        ->scalarNode('identifier_field')->defaultValue(null)->end()
                        ->scalarNode('password_field')->defaultValue(null)->end()
                        ->scalarNode('jwt_cookie_builder')->defaultValue(null)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
