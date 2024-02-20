<?php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Loggable\LoggableListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;

final class LoggableEventListener
{

    /**
     * @var Security
     */
    private Security $security;

    private LoggableListener $loggable;

    private ObjectManager $manager;

    public function __construct(Security $security, LoggableListener $loggable, EntityManagerInterface $manager)
    {
        $this->security = $security;
        $this->loggable = $loggable;
        $this->manager = $manager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->security->getToken() instanceof SwitchUserToken) {
            $this->loggable->setUsername($this->security->getUser()->getUserIdentifier());
        }
    }

}
