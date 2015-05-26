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

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

/**
 * I18n Router implementation.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nRouter extends Router
{
    /**
     * @var I18nMatcher
     */
    private $i18nMatcher;

    /**
     * @var I18nUrlGenerator
     */
    private $i18nGenerator;

    /**
     * @var I18nHelper
     */
    private $i18nHelper;


    /**
     * Sets the helper.
     *
     * @param I18nHelper $helper
     */
    public function setI18nHelper(I18nHelper $helper)
    {
        $this->i18nHelper = $helper;
    }

    /**
     * Returns the i18nHelper.
     *
     * @return I18nHelper
     */
    public function getI18nHelper()
    {
        return $this->i18nHelper;
    }

    /**
     * Returns the i18n matcher.
     *
     * @return I18nMatcher
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
     * @return I18nUrlGenerator
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
    public function getRouteCollection()
    {
        return $this->i18nHelper->getI18nLoader()->load($this->getOriginalRouteCollection());
    }

    /**
     * Returns the original route collection.
     *
     * @return \Symfony\Component\Routing\RouteCollection
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
     * Generates a URL from the given parameters.
     *
     * @param  string  $name       The name of the route
     * @param  array   $parameters An array of parameters
     * @param  Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        return $this->getI18nGenerator()->generate($name, $parameters, $absolute);
    }
}
