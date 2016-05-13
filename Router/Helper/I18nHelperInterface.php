<?php

namespace JMS\I18nRoutingBundle\Router\Helper;

use JMS\I18nRoutingBundle\Router\Loader\I18nLoaderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Interface I18nHelperInterface
 * @package JMS\I18nRoutingBundle\Router\Helper
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface I18nHelperInterface
{
    /**
     * Sets the i18n loader.
     *
     * @param I18nLoaderInterface $i18nLoader
     */
    public function setI18nLoader(I18nLoaderInterface $i18nLoader);

    /**
     * Returns the i18n loader.
     *
     * @return I18nLoaderInterface
     */
    public function getI18nLoader();

    /**
     * Sets the configuration.
     *
     * @param array $config
     */
    public function setConfig(array $config);

    /**
     * Returns the config.
     *
     * @param string $key
     * @return mixed
     */
    public function getConfig($key = null);

    /**
     * Returns the request or null if not available.
     *
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    public function getRequest();

    /**
     * Creates the locale resolver.
     *
     * @return \JMS\I18nRoutingBundle\Router\Resolver\LocaleResolverInterface
     */
    public function createLocaleResolver();

    /**
     * Creates the i18n url matcher.
     *
     * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface|\Symfony\Component\Routing\Matcher\UrlMatcherInterface $fallbackMatcher
     * @return \JMS\I18nRoutingBundle\Router\Matcher\I18nMatcherInterface
     */
    public function createMatcher($fallbackMatcher);

    /**
     * Creates the i18n url generator.
     *
     * @param UrlGeneratorInterface $fallbackGenerator
     * @param RouteCollection $routes
     * @param LoggerInterface $logger
     * @return \JMS\I18nRoutingBundle\Router\Generator\I18nUrlGeneratorInterface
     */
    public function createUrlGenerator(
        UrlGeneratorInterface $fallbackGenerator,
        RouteCollection $routes = null,
        LoggerInterface $logger = null
    );
}
