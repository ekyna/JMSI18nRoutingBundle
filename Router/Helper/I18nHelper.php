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

namespace JMS\I18nRoutingBundle\Router\Helper;

use JMS\I18nRoutingBundle\Exception\RuntimeException;
use JMS\I18nRoutingBundle\Router\Loader\I18nLoaderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class I18nHelper
 * @package JMS\I18nRoutingBundle\Router
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nHelper implements I18nHelperInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var I18nLoaderInterface
     */
    private $i18nLoader;

    /**
     * @var array
     */
    private $config;


    /**
     * Constructor.
     *
     * @param RequestStack $request_stack
     * @param array        $config
     */
    public function __construct(RequestStack $request_stack, array $config)
    {
        $this->requestStack = $request_stack;

        $this->setConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    public function setI18nLoader(I18nLoaderInterface $i18nLoader)
    {
        $this->i18nLoader = $i18nLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function getI18nLoader()
    {
        if (null === $this->i18nLoader) {
            throw new \RuntimeException('Please call setI18nLoader first.');
        }
        return $this->i18nLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        if (null === $this->config) {
            $this->config = array(
                'default_locale'   => 'en',
                'locales'          => array('en'),
                'catalogue'        => 'routes',
                'strategy'         => 'custom',
                'redirect_to_host' => true,
                'host_map'         => array(),
                'cookie'           => array(
                    'enabled'  => true,
                    'name'     => 'hl',
                    'lifetime' => 31536000,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => false,
                    'httponly' => false,
                ),
                'class' => array(
                    'locale_resolver' => 'JMS\I18nRoutingBundle\Router\Resolver\DefaultLocaleResolver',
                    'matcher'         => 'JMS\I18nRoutingBundle\Router\Matcher\I18nMatcher',
                    'generator'       => 'JMS\I18nRoutingBundle\Router\Generator\I18nUrlGenerator',
                ),
            );
        }
        $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($key = null)
    {
        if (null === $this->config) {
            throw new RuntimeException('Configuration must be set first.');
        }
        if ($key) {
            return $this->config[$key];
        }
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->requestStack->getMasterRequest(); // TODO check use of master / current request
    }

    /**
     * {@inheritdoc}
     */
    public function createLocaleResolver()
    {
        $class = $this->config['class']['locale_resolver'];

        return new $class(
            $this->config['cookie']['name'],
            array_flip($this->config['host_map'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createMatcher(
        $fallbackMatcher,
        RouteCollection $routes = null,
        RequestContext $context = null
    ) {
        if (! ($fallbackMatcher instanceof RequestMatcherInterface
            || $fallbackMatcher instanceof UrlMatcherInterface)) {
            throw new \InvalidArgumentException(
                'Fallback matcher must implement either Symfony\Component\Routing\Matcher\RequestMatcherInterface '.
                'or Symfony\Component\Routing\Matcher\UrlMatcherInterface'
            );
        }

        $class = $this->config['class']['matcher'];

        return new $class($this, $fallbackMatcher, $routes, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function createUrlGenerator(
        UrlGeneratorInterface $fallbackGenerator,
        RouteCollection $routes = null,
        LoggerInterface $logger = null
    ) {
        $class = $this->config['class']['generator'];

        return new $class($this, $fallbackGenerator, $routes, $logger);
    }
}
