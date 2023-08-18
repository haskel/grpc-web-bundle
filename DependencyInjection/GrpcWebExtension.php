<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\DependencyInjection;

use Haskel\GrpcWebBundle\Attribute\Service;
use Haskel\GrpcWebBundle\Constant\Tag;
use InvalidArgumentException;
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

        $this->configureSecurity($config, $container);
    }

    private function configureSecurity(array $config, ContainerBuilder $container): void
    {
        if (!isset($config['security'])) {
            return;
        }

        $securityConfig = $config['security'];
        $authenticator = $container->getDefinition('haskel.grpc_web.security.grpc_login_authenticator');

        if (!isset($securityConfig['sign_in_request_class'])) {
            throw new InvalidArgumentException('You must provide a sign in request class');
        }
        $authenticator->setArgument('$signInRequestClass', $securityConfig['sign_in_request_class']);

        if (isset($securityConfig['identifier_field'])) {
            $authenticator->setArgument('$identifierField', $securityConfig['identifier_field']);
        }

        if (isset($securityConfig['password_field'])) {
            $authenticator->setArgument('$passwordField', $securityConfig['password_field']);
        }

        if (isset($securityConfig['jwt_cookie_builder'])) {
            $jwtCookieBuilder = new Definition($securityConfig['jwt_cookie_builder']);
            $jwtCookieBuilder->setAutowired(true);
            $authenticator->setArgument('$jwtCookieBuilder', $jwtCookieBuilder);
        }

        if (isset($securityConfig['success_response_builder'])) {
            $successResponseBuilder = new Definition($securityConfig['success_response_builder']);
            $successResponseBuilder->setAutowired(true);
            $authenticator->setArgument('$successResponseBuilder', $successResponseBuilder);
        }

        if (isset($securityConfig['failure_response_builder'])) {
            $failureResponseBuilder = new Definition($securityConfig['failure_response_builder']);
            $failureResponseBuilder->setAutowired(true);
            $authenticator->setArgument('$failureResponseBuilder', $failureResponseBuilder);
        }
    }
}
