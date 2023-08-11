<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\DependencyInjection;

use Haskel\GrpcWebBundle\Attribute\Service;
use Haskel\GrpcWebBundle\Constant\Tag;
use ReflectionClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Definition;

class GrpcWebExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $container->registerAttributeForAutoconfiguration(
            Service::class,
            static function (
                ChildDefinition $definition,
                Service $attribute,
                ReflectionClass $reflector
            ) {
                $tagAttributes = get_object_vars($attribute);
                $definition->addTag(Tag::GRPC_SERVICE, $tagAttributes);
                $definition->addTag('controller.service_arguments');
            }
        );

        $authentificator = $container->getDefinition('haskel.grpc_web.security.grpc_login_authenticator');

        if (!isset($config['security']['sign_in_request_class'])) {
            throw new \InvalidArgumentException('You must provide a sign in request class');
        }
        $authentificator->setArgument('$signInRequestClass', $config['security']['sign_in_request_class']);

        if (isset($config['security']['identifier_field'])) {
            $authentificator->setArgument('$identifierField', $config['security']['identifier_field']);
        }
        if (isset($config['security']['password_field'])) {
            $authentificator->setArgument('$passwordField', $config['security']['password_field']);
        }
        if (isset($config['security']['jwt_cookie_builder'])) {
            $jwtCookieBuilder = new Definition($config['security']['jwt_cookie_builder']);
            $jwtCookieBuilder->setAutowired(true);
            $authentificator->setArgument('$jwtCookieBuilder', $jwtCookieBuilder);
        }
        if (isset($config['security']['success_response_builder'])) {
            $successResponseBuilder = new Definition($config['security']['success_response_builder']);
            $successResponseBuilder->setAutowired(true);
            $authentificator->setArgument('$successResponseBuilder', $successResponseBuilder);
        }
        if (isset($config['security']['failure_response_builder'])) {
            $failureResponseBuilder = new Definition($config['security']['failure_response_builder']);
            $failureResponseBuilder->setAutowired(true);
            $authentificator->setArgument('$failureResponseBuilder', $failureResponseBuilder);
        }
    }
}
