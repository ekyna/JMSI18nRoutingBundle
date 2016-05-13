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

namespace JMS\I18nRoutingBundle\Router;

use JMS\I18nRoutingBundle\Router\Helper\I18nHelperAwareInterface;
use JMS\I18nRoutingBundle\Router\Helper\I18nHelperInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

/**
 * I18n Router implementation.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nRouter extends Router implements I18nRouterInterface, I18nHelperAwareInterface
{
    /**
     * @var I18nHelperInterface
     */
    protected $i18nHelper;

    /**
     * @var \JMS\I18nRoutingBundle\Router\Matcher\I18nMatcherInterface
     */
    protected $i18nMatcher;

    /**
     * @var \JMS\I18nRoutingBundle\Router\Generator\I18nUrlGeneratorInterface
     */
    protected $i18nGenerator;

    /**
     * @var \Symfony\Component\Routing\RouteCollection
     */
    protected $i18nCollection;

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
            $this->i18nGenerator = $this->i18nHelper->createUrlGenerator($this->getGenerator());
        }

        return $this->i18nGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        parent::setContext($context);

        if (null !== $this->i18nMatcher) {
            $this->getI18nMatcher()->setContext($context);
        }
        if (null !== $this->i18nGenerator) {
            $this->getI18nGenerator()->setContext($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        if (null === $this->i18nCollection) {
            $this->i18nCollection = $this->i18nHelper->getI18nLoader()->load($this->getOriginalRouteCollection());
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
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getI18nGenerator()->generate($name, $parameters, $referenceType);
    }
}
