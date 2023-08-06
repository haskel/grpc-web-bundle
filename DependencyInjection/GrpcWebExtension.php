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
    }
}
