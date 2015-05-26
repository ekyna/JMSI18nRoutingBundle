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

namespace JMS\I18nRoutingBundle\DependencyInjection\Compiler;

use JMS\I18nRoutingBundle\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ConvertRouterPass
 *
 * Converts the routers into i18n routers.
 *
 * @package JMS\I18nRoutingBundle\DependencyInjection\Compiler
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class ConvertRouterPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $classMap = array(
        'Symfony\Bundle\FrameworkBundle\Routing\Router' => 'JMS\I18nRoutingBundle\Router\I18nRouter',
//        'Symfony\Cmf\Component\Routing\DynamicRouter'   => 'JMS\I18nRoutingBundle\Router\Cmf\I18nDynamicRouter',
//        'Symfony\Cmf\Component\Routing\ChainRouter'     => 'JMS\I18nRoutingBundle\Router\Cmf\I18nChainRouter',
    );

    /**
     * Returns the new router class.
     *
     * @param string $class
     * @param ContainerBuilder $container
     * @return string|false
     */
    private function getI18nClass($class, ContainerBuilder $container)
    {
        if (preg_match('~^%[a-z0-9-_\.]+%$~i', $class)) {
            if (!$container->hasParameter($class = trim($class, '%'))) {
                throw new RuntimeException(sprintf('Can\'t resolve %s parameter.', $class));
            }
            $class = $container->getParameter($class);
        }
        $reflection = new \ReflectionClass($class);

        foreach ($this->classMap as $base => $i18nClass) {
            if ($class === $base || $reflection->isSubclassOf($base)) {
                return $i18nClass;
            }
        }

        throw new RuntimeException(sprintf('Can\'t convert %s into a i18n router.', $class));
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('jms_i18n_routing.routers_ids')) {
            return;
        }

        // Checks that translator is enabled
        $translatorDef = $container->findDefinition('translator');
        if ('%translator.identity.class%' === $translatorDef->getClass()) {
            throw new \RuntimeException(
                'The JMSI18nRoutingBundle requires Symfony2\'s translator to be enabled. Please '.
                'make sure to un-comment the respective section in the framework config.'
            );
        }

        $routersIds = $container->getParameter('jms_i18n_routing.routers_ids');

        foreach ($routersIds as $id) {
            if (!$container->hasDefinition($id)) {
                throw new RuntimeException(sprintf('The router "%s" does not exists.', $id));
            }

            // Original router definition
            $router = $container->getDefinition($id);

            // Get the new i18n router class
            $class = $this->getI18nClass($router->getClass(), $container);

            // Change class and inject i18n helper
            $router
                ->setClass($class)
                ->addMethodCall('setI18nHelper', array(new Reference('jms_i18n_routing.helper')))
            ;
        }
    }
}
