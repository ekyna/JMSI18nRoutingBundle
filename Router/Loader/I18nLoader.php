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

namespace JMS\I18nRoutingBundle\Router\Loader;

use JMS\I18nRoutingBundle\Router\Loader\Strategy\PatternGenerationStrategyInterface;
use JMS\I18nRoutingBundle\Router\Loader\Strategy\RouteExclusionStrategyInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * This loader expands all routes which are eligible for i18n.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class I18nLoader implements I18nLoaderInterface
{
    // TODO move into the interface (FOSJsRouting BC)
    const ROUTING_PREFIX = '__RG__';

    /**
     * @var RouteExclusionStrategyInterface
     */
    private $routeExclusionStrategy;

    /**
     * @var PatternGenerationStrategyInterface
     */
    private $patternGenerationStrategy;

    /**
     * Constructor.
     *
     * @param RouteExclusionStrategyInterface $routeExclusionStrategy
     * @param PatternGenerationStrategyInterface $patternGenerationStrategy
     */
    public function __construct(
        RouteExclusionStrategyInterface $routeExclusionStrategy,
        PatternGenerationStrategyInterface $patternGenerationStrategy
    ) {
        $this->routeExclusionStrategy = $routeExclusionStrategy;
        $this->patternGenerationStrategy = $patternGenerationStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function load(RouteCollection $collection)
    {
        $i18nCollection = new RouteCollection();
        foreach ($collection->getResources() as $resource) {
            $i18nCollection->addResource($resource);
        }
        $this->patternGenerationStrategy->addResources($i18nCollection);

        foreach ($collection->all() as $name => $route) {
            if ($this->routeExclusionStrategy->shouldExcludeRoute($name, $route)) {
                $i18nCollection->add($name, $route);
                continue;
            }

            foreach ($this->patternGenerationStrategy->generateI18nPatterns($name, $route) as $pattern => $locales) {
                // If this pattern is used for more than one locale, we need to keep the original route.
                // We still add individual routes for each locale afterwards for faster generation.
                if (count($locales) > 1) {
                    $catchMultipleRoute = clone $route;
                    $catchMultipleRoute->setPattern($pattern);
                    $catchMultipleRoute->setDefault('_locales', $locales);
                    $i18nCollection->add(implode('_', $locales).I18nLoader::ROUTING_PREFIX.$name, $catchMultipleRoute);
                }

                foreach ($locales as $locale) {
                    $localeRoute = clone $route;
                    $localeRoute->setPattern($pattern);
                    $localeRoute->setDefault('_locale', $locale);
                    $i18nCollection->add($locale.I18nLoader::ROUTING_PREFIX.$name, $localeRoute);
                }
            }
        }

        return $i18nCollection;
    }
}
