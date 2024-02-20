<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

final class ClientToObjectEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Security
     */
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents()
    {

        return [
            KernelEvents::VIEW => ['setClient', EventPriorities::PRE_WRITE],
        ];
    }

    /**
     * @param ViewEvent $event
     */
    public function setClient(ViewEvent $event): void
    {
        $object = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (Request::METHOD_POST !== $method) {
            return;
        }

        /** @var OAuthUser $user */
        $user = $this->security->getUser();
        $clientId = null;

        if ($user && !str_starts_with($user->getUserIdentifier(), '_service') && !str_starts_with($user->getUserIdentifier(), '_microservice')) {
            $clientId = $this->security->getUser()->getUserIdentifier();
        }

        if (property_exists($object, 'clientId')) {
            $object->setClientId($clientId);
        }


        return;
    }
}
