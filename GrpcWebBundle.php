<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle;

use Haskel\GrpcWebBundle\DependencyInjection\RouterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GrpcWebBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RouterCompilerPass());
    }
}
