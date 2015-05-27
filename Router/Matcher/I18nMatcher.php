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

namespace JMS\I18nRoutingBundle\Router\Matcher;

use JMS\I18nRoutingBundle\Exception\NotAcceptableLanguageException;
use JMS\I18nRoutingBundle\Router\Helper\I18nHelperInterface;
use JMS\I18nRoutingBundle\Router\Loader\I18nLoaderInterface;
use JMS\I18nRoutingBundle\Router\Resolver\LocaleResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Class I18nMatcher
 * @package JMS\I18nRoutingBundle\Router\Matcher
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nMatcher implements I18nMatcherInterface
{
    /**
     * @var UrlMatcherInterface
     */
    private $fallbackMatcher;

    /**
     * @var LocaleResolverInterface
     */
    private $localeResolver;

    /**
     * @var I18nHelperInterface
     */
    private $helper;


    /**
     * Constructor.
     *
     * @param I18nHelperInterface $helper
     * @param UrlMatcherInterface $fallbackMatcher
     */
    public function __construct(I18nHelperInterface $helper, UrlMatcherInterface $fallbackMatcher)
    {
        $this->helper          = $helper;
        $this->fallbackMatcher = $fallbackMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocaleResolver()
    {
        if (null === $this->localeResolver) {
            $this->localeResolver = $this->helper->createLocaleResolver();
        }

        return $this->localeResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocalResolver(LocaleResolverInterface $resolver)
    {
        $this->localeResolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        return $this->matchI18n($this->fallbackMatcher->match($url), $url);
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        if (!$this->fallbackMatcher instanceof RequestMatcherInterface) {
            // fallback to the default UrlMatcherInterface
            return $this->matchI18n($this->fallbackMatcher->match($pathInfo), $pathInfo);
        }

        return $this->matchI18n($this->fallbackMatcher->matchRequest($request), $pathInfo);
    }

    /**
     * Match i18n url.
     *
     * @param array $params
     * @param string $url
     *
     * @return array|false
     */
    private function matchI18n(array $params, $url)
    {
        if (false === $params) {
            return false;
        }

        $context = $this->fallbackMatcher->getContext();
        $hostMap = $this->helper->getConfig('host_map');

        $request = $this->helper->getRequest();

        if (isset($params['_locales'])) {
            if (false !== $pos = strpos($params['_route'], I18nLoaderInterface::ROUTING_PREFIX)) {
                $params['_route'] = substr($params['_route'], $pos + strlen(I18nLoaderInterface::ROUTING_PREFIX));
            }

            if (!($currentLocale = $context->getParameter('_locale')) && null !== $request) {
                $currentLocale = $this->getLocaleResolver()->resolveLocale($request, $params['_locales']);

                // If the locale resolver was not able to determine a locale, then all efforts to
                // make an informed decision have failed. Just display something as a last resort.
                if (!$currentLocale) {
                    $currentLocale = reset($params['_locales']);
                }
            }

            if (!in_array($currentLocale, $params['_locales'], true)) {
                // TODO: We might want to allow the user to be redirected to the route for the given locale if
                //       it exists regardless of whether it would be on another domain, or the same domain.
                //       Below we assume that we do not want to redirect always.

                // if the available locales are on a different host, throw a ResourceNotFoundException
                if ($hostMap) {
                    $availableHosts = array_map(function($locale) use ($hostMap) {
                        return $hostMap[$locale];
                    }, $params['_locales']);

                    $differentHost = true;
                    foreach ($availableHosts as $host) {
                        if ($hostMap[$currentLocale] === $host) {
                            $differentHost = false;
                            break;
                        }
                    }

                    if ($differentHost) {
                        throw new ResourceNotFoundException(sprintf('The route "%s" is not available on the current host "%s", but only on these hosts "%s".',
                            $params['_route'], $hostMap[$currentLocale], implode(', ', $availableHosts)));
                    }
                }

                // no host map, or same host means that the given locale is not supported for this route
                throw new NotAcceptableLanguageException($currentLocale, $params['_locales']);
            }

            unset($params['_locales']);
            $params['_locale'] = $currentLocale;
        } else if (isset($params['_locale']) && 0 < $pos = strpos($params['_route'], I18nLoaderInterface::ROUTING_PREFIX)) {
            $params['_route'] = substr($params['_route'], $pos + strlen(I18nLoaderInterface::ROUTING_PREFIX));
        }

        // check if the matched route belongs to a different locale on another host
        if (isset($params['_locale'])
            && isset($hostMap[$params['_locale']])
            && $context->getHost() !== $host = $hostMap[$params['_locale']]) {
            if (!$this->helper->getConfig('redirect_to_host')) {
                throw new ResourceNotFoundException(sprintf(
                    'Resource corresponding to pattern "%s" not found for locale "%s".',
                    $url, $this->fallbackMatcher->getContext()->getParameter('_locale')
                ));
            }

            return array(
                '_controller' => 'JMS\I18nRoutingBundle\Controller\RedirectController::redirectAction',
                'path'        => $url,
                'host'        => $host,
                'permanent'   => true,
                'scheme'      => $context->getScheme(),
                'httpPort'    => $context->getHttpPort(),
                'httpsPort'   => $context->getHttpsPort(),
                '_route'      => $params['_route'],
            );
        }

        // if we have no locale set on the route, we try to set one according to the localeResolver
        // if we don't do this all _internal routes will have the default locale on first request

        if (!isset($params['_locale']) && null !== $request) {
            if ($locale = $this->getLocaleResolver()->resolveLocale($request, $this->helper->getConfig('locales'))) {
                $params['_locale'] = $locale;
            }
        }

        return $params;
    }
}
