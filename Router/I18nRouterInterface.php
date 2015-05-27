<?php

namespace JMS\I18nRoutingBundle\Router;

/**
 * Interface I18nRouterInterface
 * @package JMS\I18nRoutingBundle\Router
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface I18nRouterInterface
{
    /**
     * Returns the original route collection.
     *
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getOriginalRouteCollection();
}
