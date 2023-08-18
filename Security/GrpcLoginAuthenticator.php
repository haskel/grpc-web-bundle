<?php

declare(strict_types=1);

namespace Haskel\GrpcWebBundle\Security;

use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Internal\Message;
use Haskel\GrpcWebBundle\Constant\ProtocolContentType;
use Haskel\GrpcWebBundle\GrpcResponse;
use Haskel\GrpcWebBundle\Service\GrpcResponseBuilderInterface;
use Haskel\GrpcWebBundle\Message\LengthPrefixedMessage;
use Haskel\GrpcWebBundle\Message\StatusCode;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use LogicException;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GrpcLoginAuthenticator implements InteractiveAuthenticatorInterface
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private EventDispatcherInterface $dispatcher,
        private JwtCookieBuilderInterface $jwtCookieBuilder,
        private GrpcResponseBuilderInterface $successResponseBuilder,
        private GrpcResponseBuilderInterface $failureResponseBuilder,
        private string $signInRequestClass,
        private string $userIdentifierField = 'email',
        private string $passwordField = 'password',
    ) {
        if (!is_subclass_of($signInRequestClass, Message::class)) {
            throw new LogicException(sprintf('signInRequestClass must be a subclass of %s', Message::class));
        }
    }

    public function supports(Request $request): ?bool
    {
        $contentType = $request->getContentTypeFormat();

        if (!$contentType || !str_contains($contentType, 'grpc-web')) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $body = match ($request->headers->get('content-type')) {
            ProtocolContentType::GRPC_WEB => $request->getContent(),
            ProtocolContentType::GRPC_WEB_TEXT => base64_decode($request->getContent()),
            default => $request->getContent(),
        };

        $lengthPrefixedMessage = LengthPrefixedMessage::decode($body);
        $message = new $this->signInRequestClass();
        if (!$message instanceof Message) {
            throw new LogicException(sprintf('signInRequestClass must be a subclass of %s', Message::class));
        }
        $message->mergeFromString($lengthPrefixedMessage->getMessage());

        $userIdentifierGetter = 'get'.ucfirst(preg_replace_callback('/_([a-z])/', fn ($matches) => strtoupper($matches[1]), $this->userIdentifierField));
        $passwordGetter = 'get'.ucfirst(preg_replace_callback('/_([a-z])/', fn ($matches) => strtoupper($matches[1]), $this->passwordField));
        $identifier = $message->{$userIdentifierGetter}();
        $password = $message->{$passwordGetter}();

        $userBadge = new UserBadge($identifier, $this->userProvider->loadUserByIdentifier(...));
        $passport = new Passport($userBadge, new PasswordCredentials($password));

        if ($this->userProvider instanceof PasswordUpgraderInterface) {
            $passport->addBadge(new PasswordUpgradeBadge($password, $this->userProvider));
        }

        return $passport;
    }

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        return new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            throw new LogicException('The user must be an instance of UserInterface.');
        }

        $jwtCookie = $this->jwtCookieBuilder->build($user);

        $response = $this->buildSuccessResponse($user, $request, $jwtCookie);
        $response->headers->setCookie($jwtCookie);

        $this->dispatcher->dispatch(
            new AuthenticationSuccessEvent(['token' => $jwtCookie->getValue()], $user, $response),
            Events::AUTHENTICATION_SUCCESS
        );

        return $response;
    }

    protected function buildSuccessResponse(UserInterface $user, Request $request, Cookie $jwtCookie): GrpcResponse
    {
        if (is_callable($this->successResponseBuilder)) {
            return call_user_func($this->successResponseBuilder, $user, $request, $jwtCookie);
        }

        if ($this->successResponseBuilder instanceof GrpcResponse) {
            return $this->successResponseBuilder;
        }

        if ($this->successResponseBuilder instanceof GrpcResponseBuilderInterface) {
            return $this->successResponseBuilder->build($user, $request, $jwtCookie);
        }

        return new GrpcResponse(new GpbEmpty(), StatusCode::Ok);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if (is_callable($this->failureResponseBuilder)) {
            return call_user_func($this->failureResponseBuilder, $exception);
        }

        if ($this->failureResponseBuilder instanceof GrpcResponse) {
            return $this->failureResponseBuilder;
        }

        if ($this->failureResponseBuilder instanceof GrpcResponseBuilderInterface) {
            return $this->failureResponseBuilder->build($exception);
        }

        return new GrpcResponse(new GpbEmpty(), StatusCode::Unauthenticated);
    }

    public function isInteractive(): bool
    {
        return true;
    }
}
