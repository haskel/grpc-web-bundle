<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\DependencyInjection;

use Haskel\GrpcWebBundle\Constant\Tag;
use Haskel\GrpcWebBundle\Service\GrpcService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RouterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(GrpcService::class)
            ->addTag(Tag::GRPC_SERVICE)
            ->addTag('controller.service_arguments')
        ;
    }
}
