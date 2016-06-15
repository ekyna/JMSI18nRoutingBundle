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

namespace JMS\I18nRoutingBundle\Router\Generator;

use JMS\I18nRoutingBundle\Router\Helper\I18nHelperInterface;
use JMS\I18nRoutingBundle\Router\Loader\I18nLoaderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class I18nUrlGenerator
 * @package JMS\I18nRoutingBundle\Router\Generator
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nUrlGenerator extends UrlGenerator implements I18nUrlGeneratorInterface
{
    /**
     * @var I18nHelperInterface
     */
    private $helper;

    /**
     * @var UrlGeneratorInterface
     */
    private $fallbackGenerator;

    /**
     * Constructor.
     *
     * @param I18nHelperInterface   $helper
     * @param UrlGeneratorInterface $fallbackGenerator
     * @param RouteCollection       $routes
     * @param LoggerInterface|null  $logger
     */
    public function __construct(
        I18nHelperInterface   $helper,
        UrlGeneratorInterface $fallbackGenerator,
        RouteCollection       $routes = null,
        LoggerInterface       $logger = null
    ) {
        $this->helper            = $helper;
        $this->fallbackGenerator = $fallbackGenerator;

        $this->routes            = $routes;
        $this->context           = $fallbackGenerator->getContext();
        $this->logger            = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
        $this->fallbackGenerator->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        $context = $this->fallbackGenerator->getContext();

        // determine the most suitable locale to use for route generation
        $currentLocale = $context->getParameter('_locale');
        if (isset($parameters['_locale'])) {
            $locale = $parameters['_locale'];
        } else if ($currentLocale) {
            $locale = $currentLocale;
        } else {
            $locale = $this->helper->getConfig('default_locale');
        }

        $hostMap = $this->helper->getConfig('host_map');

        // if the locale is changed, and we have a host map, then we need to
        // generate an absolute URL
        if ($currentLocale && $currentLocale !== $locale && $hostMap) {
            $absolute = true;
        }

        // If we've got a route collection, try to generate with it. Else try with the fallback generator
        $callable = null !== $this->routes ? 'parent::generate' : array($this->fallbackGenerator, 'generate');
        $args = array($locale.I18nLoaderInterface::ROUTING_PREFIX.$name, $parameters, $absolute);

        // if an absolute URL is requested, we set the correct host
        if ($absolute && $hostMap) {
            $currentHost = $context->getHost();
            $context->setHost($hostMap[$locale]);

            try {
                $url = call_user_func_array($callable, $args);
                $context->setHost($currentHost);
                return $url;
            } catch (RouteNotFoundException $ex) {
                $context->setHost($currentHost);
            }
        } else {
            try {
                $url = call_user_func_array($callable, $args);
                return $url;
            } catch (RouteNotFoundException $ex) {
            }
        }

        // use the default behavior if no localized route exists
        return $this->fallbackGenerator->generate($name, $parameters, $absolute);
    }
}
