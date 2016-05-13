<?php

namespace JMS\I18nRoutingBundle\Router\Matcher;

use JMS\I18nRoutingBundle\Router\Resolver\LocaleResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Interface I18nMatcherInterface
 * @package JMS\I18nRoutingBundle\Router\Matcher
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface I18nMatcherInterface extends UrlMatcherInterface
{
    /**
     * Returns the locale resolver.
     *
     * @return LocaleResolverInterface
     */
    public function getLocaleResolver();

    /**
     * Sets the locale resolver (for tests).
     *
     * @param LocaleResolverInterface $resolver
     */
    public function setLocalResolver(LocaleResolverInterface $resolver);

    /**
     * Tries to match a URL with a set of routes.
     *
     * Returns false if no route matches the URL.
     *
     * @param  string $url URL to be parsed
     *
     * @return array|false An array of parameters or false if no route matches
     */
    public function match($url);

    /**
     * To make compatible with Symfony <2.4
     *
     * @param Request $request
     *
     * @return array
     */
    public function matchRequest(Request $request);
}
