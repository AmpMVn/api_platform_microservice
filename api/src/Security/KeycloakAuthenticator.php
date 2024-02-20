<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Signature\Exception\ExpiredSignatureException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class KeycloakAuthenticator extends OAuth2Authenticator
{
    private $clientRegistry;

    private $entityManager;

    private $router;

    private $httpClient;

    private $parameterBag;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, HttpClientInterface $httpClient, ParameterBagInterface $parameterBag)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'oauth_check' || $request->headers->get('authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('rentsoft_ms_user_keycloak_api_gateway');
        $client->getOAuth2Provider()->authServerUrl = $this->parameterBag->get('api_gateway_connector')['auth_server_url_intern'];

        $isService = false;
        $user = null;

        if ($request->headers->get('authorization')) {

            try {
                $certs = $this->httpClient->request("GET", $this->parameterBag->get('api_gateway_connector')['auth_server_url_intern_certs'] . "/realms/rs-platform/protocol/openid-connect/certs")->toArray();
                if (count($certs) && $certs['keys'][0]['use'] == 'sig') {
                    $cert = $certs['keys'][0];
                } elseif (isset($certs['keys'][1]) && $certs['keys'][1]['use'] == 'sig') {
                    $cert = $certs['keys'][1];
                } else {
                    throw new CustomUserMessageAuthenticationException('No Auth server kyes');
                }

                $keyString = $cert['x5c'][0];

                $key = "-----BEGIN CERTIFICATE-----\n" .
                    $keyString . "\n" .
                    "-----END CERTIFICATE----- ";

                $tokenString = $request->headers->get('authorization');

                if (preg_match('/Bearer\s(\S+)/', $tokenString, $matches)) {
                    $tokenString = $matches[1];
                }

                $config = Configuration::forSymmetricSigner(new Signer\Rsa\Sha256(), InMemory::plainText($key));
                $token = $config->parser()->parse($tokenString);


                if ($token->isExpired(new \DateTime())) {
                    throw new ExpiredSignatureException();
                }

                $validator = $config->validator();
                $signedWith = new SignedWith($config->signer(), $config->verificationKey());

                if ($validator->validate($token, $signedWith)) {
                    $idClient = $token->claims()->get('rs_client_id');

                    if($token->claims()->get('group_id')) {
                        $idClient = $token->claims()->get('group_id');
                    }

                    if (!$idClient) {
                        if (in_array("ROLE_SERVICE", $token->claims()->get('realm_access')['roles'])) {
                            $isService = true;
                            $idClient = $token->claims()->get('clientId');
                        }
                    }
                } else {
                    throw new InvalidCsrfTokenException();
                }

            } catch (\Exception $e) {
                throw new CustomUserMessageAuthenticationException($e);
            }
        } else {
            $client->getOAuth2Provider()->authServerUrl = $this->parameterBag->get('api_gateway_connector')['auth_server_url_intern_tokens'];

            $accessToken = $this->fetchAccessToken($client);
            /** @var KeycloakResourceOwner $user */
            $user = $client->fetchUserFromToken($accessToken);

            $idClient = $user->toArray()['rs_client_id'];
        }

        if ($isService) {
            $passport = new SelfValidatingPassport(new UserBadge($idClient), []);
            $passport->setAttribute('context', 'email');

        } else {
            $passport = new SelfValidatingPassport(new UserBadge($user ? $user->getId() : $idClient), []);
            $passport->setAttribute('context', 'email');
        }

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($request->attributes->get('_route') === 'oauth_check') {
            $targetUrl = $this->router->generate('api_doc');
            return new RedirectResponse($targetUrl);
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
