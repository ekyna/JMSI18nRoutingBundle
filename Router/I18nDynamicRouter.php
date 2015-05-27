<?php

namespace JMS\I18nRoutingBundle\Router;

use JMS\I18nRoutingBundle\Router\Helper\I18nHelperAwareInterface;
use JMS\I18nRoutingBundle\Router\Helper\I18nHelperInterface;
use Symfony\Cmf\Component\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class I18nDynamicRouter
 * @package JMS\I18nRoutingBundle\Router\Cmf
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nDynamicRouter extends DynamicRouter implements I18nRouterInterface, I18nHelperAwareInterface
{
    /**
     * @var I18nHelperInterface
     */
    private $i18nHelper;

    /**
     * @var \JMS\I18nRoutingBundle\Router\Matcher\I18nMatcherInterface
     */
    private $i18nMatcher;

    /**
     * @var \JMS\I18nRoutingBundle\Router\Generator\I18nUrlGeneratorInterface
     */
    private $i18nGenerator;

    /**
     * @var \Symfony\Component\Routing\RouteCollection
     */
    private $i18nCollection;


    /**
     * {@inheritdoc}
     */
    public function setI18nHelper(I18nHelperInterface $helper)
    {
        $this->i18nHelper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getI18nHelper()
    {
        return $this->i18nHelper;
    }

    /**
     * Returns the i18n matcher.
     *
     * @return \JMS\I18nRoutingBundle\Router\Matcher\I18nMatcherInterface
     */
    public function getI18nMatcher()
    {
        if (null === $this->i18nMatcher) {
            $this->i18nMatcher = $this->i18nHelper->createMatcher($this->getMatcher());
        }

        return $this->i18nMatcher;
    }

    /**
     * Returns the i18n url generator.
     *
     * @return \JMS\I18nRoutingBundle\Router\Generator\I18nUrlGeneratorInterface
     */
    public function getI18nGenerator()
    {
        if (null === $this->i18nGenerator) {
            // TODO Create a new UrlGenerator as the default one use the provider and not the i18n route collection.
            $this->i18nGenerator = $this->i18nHelper->createUrlGenerator($this->getGenerator());
        }

        return $this->i18nGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        if (null === $this->i18nCollection) {
            $this->i18nCollection = $this->i18nHelper->getI18nLoader()->load($this->getOriginalRouteCollection());
            var_dump($this->i18nCollection);
            exit();
        }

        return $this->i18nCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalRouteCollection()
    {
        return parent::getRouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        return $this->getI18nMatcher()->match($url);
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        return $this->getI18nMatcher()->matchRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        return $this->getI18nGenerator()->generate($name, $parameters, $absolute);
    }
}
