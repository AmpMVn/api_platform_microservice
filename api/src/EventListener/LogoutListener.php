<?php

namespace App\EventListener;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    /**
     * @var Security
     */
    private Security $security;

    private ClientRegistry $clientRegistry;

    private ParameterBagInterface $parameterBag;

    public function __construct(Security $security, ClientRegistry $clientRegistry, ParameterBagInterface $parameterBag)
    {
        $this->security = $security;
        $this->clientRegistry = $clientRegistry;
        $this->parameterBag = $parameterBag;
    }

    /**
    * @param LogoutEvent $logoutEvent
    * @return void
    */
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent): void
    {
        $client = $this->clientRegistry->getClient('rentsoft_ms_user_keycloak_admin_cli');
        $client->getOAuth2Provider()->authServerUrl = $this->parameterBag->get('api_gateway_connector')['auth_server_url_intern_tokens'];
        $accessToken = $client->getOAuth2Provider()->getAccessToken('client_credentials');;
        $options['headers']['Authorization'] = "Bearer " . $accessToken->getToken();
        $request = $client->getOAuth2Provider()->getRequest('POST', $this->parameterBag->get('api_gateway_connector')['auth_server_url_intern'] . '/admin/realms/rs-platform/users/' . $this->security->getUser()->getUserIdentifier() . '/logout', $options);

        $res = $client->getOAuth2Provider()->getResponse($request);

        $this->security->getUser()->eraseCredentials();
    }
}
