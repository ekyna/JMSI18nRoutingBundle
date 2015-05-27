<?php

/*
 * Copyright 2012 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\I18nRoutingBundle\EventListener;

use JMS\I18nRoutingBundle\Router\Helper\I18nHelperInterface;
use JMS\I18nRoutingBundle\Router\Resolver\LocaleResolverInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Chooses the default locale.
 *
 * This listener chooses the default locale to use on the first request of a
 * user to the application.
 *
 * This listener is only active if the strategy is "prefix".
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class LocaleChoosingListener
{
    /**
     * @var I18nHelperInterface
     */
    private $helper;

    /**
     * @var LocaleResolverInterface
     */
    private $localeResolver;

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
     * Kernel exception event handler.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();
        if ('' !== rtrim($request->getPathInfo(), '/')) {
            return;
        }

        $ex = $event->getException();
        if (!$ex instanceof NotFoundHttpException || !$ex->getPrevious() instanceof ResourceNotFoundException) {
            return;
        }

        $locale = $this
            ->getLocaleResolver()
            ->resolveLocale($request, $this->helper->getConfig('locales'))
            ?: $this->helper->getConfig('default_locale')
        ;
        $request->setLocale($locale);

        $params = $request->query->all();
        unset($params['hl']);

        $event->setResponse(new RedirectResponse(
            $request->getBaseUrl().'/'.$locale.'/'.($params ? '?'.http_build_query($params) : '')
        ));
    }

    /**
     * Returns the locale resolver.
     *
     * @return LocaleResolverInterface
     */
    private function getLocaleResolver()
    {
        if (null === $this->localeResolver) {
            $this->localeResolver = $this->helper->createLocaleResolver();
        }

        return $this->localeResolver;
    }
}
