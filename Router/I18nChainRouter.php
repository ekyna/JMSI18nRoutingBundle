<?php

namespace JMS\I18nRoutingBundle\Router;

use Symfony\Cmf\Component\Routing\ChainRouteCollection;
use Symfony\Cmf\Component\Routing\ChainRouter;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class I18nChainRouter
 * @package JMS\I18nRoutingBundle\Router
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nChainRouter extends ChainRouter implements I18nRouterInterface
{
    /**
     * @var RouteCollection
     */
    protected $originalRouteCollection;

    /**
     * {@inheritdoc}
     */
    public function getOriginalRouteCollection()
    {
        if (!$this->originalRouteCollection instanceof RouteCollection) {
            $this->originalRouteCollection = new ChainRouteCollection();
            foreach ($this->all() as $router) {
                if ($router instanceof I18nRouterInterface) {
                    $this->originalRouteCollection->addCollection($router->getOriginalRouteCollection());
                } else {
                    $this->originalRouteCollection->addCollection($router->getRouteCollection());
                }
            }
        }

        return $this->originalRouteCollection;
    }
}
