<?php

namespace JMS\I18nRoutingBundle\Router\Generator;

/**
 * Interface I18nUrlGeneratorInterface
 * @package JMS\I18nRoutingBundle\Router\Generator
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface I18nUrlGeneratorInterface
{
    /**
     * Generates a URL from the given parameters.
     *
     * @param  string  $name       The name of the route
     * @param  array   $parameters An array of parameters
     * @param  Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generate($name, $parameters = array(), $absolute = false);
}
