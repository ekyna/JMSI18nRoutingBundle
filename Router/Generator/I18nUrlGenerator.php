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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class I18nUrlGenerator
 * @package JMS\I18nRoutingBundle\Router\Generator
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nUrlGenerator implements I18nUrlGeneratorInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $fallbackGenerator;

    /**
     * @var I18nHelperInterface
     */
    private $helper;


    /**
     * Constructor.
     *
     * @param I18nHelperInterface   $helper
     * @param UrlGeneratorInterface $fallbackGenerator
     */
    public function __construct(I18nHelperInterface $helper, UrlGeneratorInterface $fallbackGenerator)
    {
        $this->helper            = $helper;
        $this->fallbackGenerator = $fallbackGenerator;
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

        // if an absolute URL is requested, we set the correct host
        if ($absolute && $hostMap) {
            $currentHost = $context->getHost();
            $context->setHost($hostMap[$locale]);

            try {
                $url = $this->fallbackGenerator->generate(
                    $locale.I18nLoaderInterface::ROUTING_PREFIX.$name,
                    $parameters, $absolute
                );
                $context->setHost($currentHost);
                return $url;
            } catch (RouteNotFoundException $ex) {
                $context->setHost($currentHost);
            }
        } else {
            try {
                $url = $this->fallbackGenerator->generate(
                    $locale.I18nLoaderInterface::ROUTING_PREFIX.$name,
                    $parameters, $absolute
                );
                return $url;
            } catch (RouteNotFoundException $ex) {
            }
        }

        // use the default behavior if no localized route exists
        return $this->fallbackGenerator->generate($name, $parameters, $absolute);
    }
}
