<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class AuthController extends AbstractController
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("/oauth/login", name="oauth_login")
     */
    public function index(ClientRegistry $clientRegistry): RedirectResponse
    {
        /** @var KeycloakClient $client */
        $client = $clientRegistry->getClient('rentsoft_ms_user_keycloak_api_gateway');
        return $client->redirect([
            'profile', 'email', 'roles' // the scopes you want to access
        ]);
    }

    /**
     * @Route("/oauth/callback", name="oauth_check")
     */
    public function check()
    {

    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction() {
        $this->getUser()->eraseCredentials();

        $targetUrl = $this->router->generate('api_docs');
        //
        return new RedirectResponse($targetUrl);
    }
}
