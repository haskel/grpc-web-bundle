<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Listener;

use Haskel\GrpcWebBundle\Constant\RequestAttribute;
use ReflectionFunctionAbstract;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{
    public function onKernelController(ControllerEvent $event): void
    {
        $reflector = $event->getControllerReflector();
        if (!$reflector instanceof ReflectionFunctionAbstract) {
            return;
        }

        $returnType = $reflector->getReturnType()?->getName();
        if ($returnType === null) {
            return;
        }

        $event->getRequest()->attributes->set(RequestAttribute::RESPONSE_TYPE, $returnType);
    }
}
