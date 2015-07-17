<?php

namespace JMS\I18nRoutingBundle\EventListener;

use JMS\I18nRoutingBundle\Router\Helper\I18nHelperInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Sets the user's language as a cookie.
 *
 * This is necessary if you are not using a host map, and still would like to
 * use Varnish in front of your Symfony2 application.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class CookieSettingListener
{
    /**
     * @var I18nHelperInterface
     */
    private $helper;

    /**
     * Constructor.
     * 
     * @param I18nHelperInterface $helper
     */
    public function __construct(I18nHelperInterface $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Kernel response event handler.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // TODO setting cookie breaks varnish cache ...
        // Check if the current response contains an error.
        // If it does, do not set the cookie as the Locale may not be properly set
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()
            || !($event->getResponse()->isSuccessful() || $event->getResponse()->isRedirection())) {
            return;
        }

        $request = $event->getRequest();
        $cookie = $this->helper->getConfig('cookie');

        if (!$request->cookies->has($cookie['name'])
            || $request->cookies->get($cookie['name']) !== $request->getLocale()) {

            $event->getResponse()->headers->setCookie(new Cookie(
                $cookie['name'],
                $request->getLocale(),
                time() + $cookie['lifetime'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            ));
        }
    }
}
