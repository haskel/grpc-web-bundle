<?php
declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Security;

use DateInterval;
use DateTimeImmutable;
use Haskel\GrpcWebBundle\Constant\JwtCookie;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Cookie\JWTCookieProvider;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;

class DefaultJwtCookieBuilder implements JwtCookieBuilderInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private string $jwtCookieName = JwtCookie::DEFAULT_NAME,
        private string $jwtCookieSameSite = Cookie::SAMESITE_LAX,
        private bool $jwtCookieSecure = false,
        private bool $jwtCookieHttpOnly = true,
        private string $jwtCookiePath = '/',
        private ?string $jwtCookieDomain = null,
        private int $jwtCookieLifetime = 3600,
    ) {
    }

    public function build(UserInterface $user): Cookie
    {
        $jwt = $this->jwtManager->create($user);
        return (new JWTCookieProvider)->createCookie(
            jwt: $jwt,
            name: $this->jwtCookieName,
            expiresAt: (new DateTimeImmutable())->add(new DateInterval('PT'.$this->jwtCookieLifetime.'S')),
            sameSite: $this->jwtCookieSameSite,
            path: $this->jwtCookiePath,
            domain: $this->jwtCookieDomain,
            secure: $this->jwtCookieSecure,
            httpOnly: $this->jwtCookieHttpOnly
        );
    }
}
