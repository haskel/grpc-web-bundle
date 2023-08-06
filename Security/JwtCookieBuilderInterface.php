<?php
declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;

interface JwtCookieBuilderInterface
{
    public function build(UserInterface $user): Cookie;
}
