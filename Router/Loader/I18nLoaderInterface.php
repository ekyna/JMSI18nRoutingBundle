<?php

namespace JMS\I18nRoutingBundle\Router\Loader;

use Symfony\Component\Routing\RouteCollection;

/**
 * Interface I18nLoaderInterface
 * @package JMS\I18nRoutingBundle\Router\Loader
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface I18nLoaderInterface
{
    const ROUTING_PREFIX = '__RG__';

    /**
     * Loads and convert the route collection to the i18n route collection.
     *
     * @param RouteCollection $collection
     * @return RouteCollection
     */
    public function load(RouteCollection $collection);
}
