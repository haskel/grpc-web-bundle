<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\DependencyInjection;

use Haskel\GrpcWebBundle\Interface\GrpcService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RouterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(GrpcService::class)
            ->addTag('grpc.service')
            ->addTag('controller.service_arguments')
        ;
    }
}
