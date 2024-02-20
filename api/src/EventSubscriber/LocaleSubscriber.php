<?php
// src/EventSubscriber/LocaleSubscriber.php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private TranslatableListener $translatable;

    public function __construct(TranslatableListener $translatable)
    {
        $this->translatable = $translatable;
    }

    public function onKernelRequest(RequestEvent $event)
    {

//        dd($this->translatable);

        $request = $event->getRequest();
//        dd($request->getLocale());
        $this->translatable->setTranslationFallback(true);
        $accept_language = $request->headers->get("accept-language");
        if (empty($accept_language)) {
            return;
        }
        $arr = HeaderUtils::split($accept_language, ',;');
        if (empty($arr[0][0])) {
            return;
        }

        $locale = str_replace('-', '_', $arr[0][0]);

        $request->setLocale($locale);
        $this->translatable->setTranslatableLocale($locale);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', EventPriorities::PRE_READ]],
        ];
    }
}
