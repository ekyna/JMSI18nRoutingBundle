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
        'Symfony\Cmf\Component\Routing\ChainRouter' => array(
            'class' => '%jms_i18n_routing.chain_router.class%',
            'inject_helper' => false,
        ),
        'Symfony\Cmf\Component\Routing\DynamicRouter' => array(
            'class' => '%jms_i18n_routing.dynamic_router.class%',
            'inject_helper' => true,
        ),
        'Symfony\Bundle\FrameworkBundle\Routing\Router' => array(
            'class' => '%jms_i18n_routing.router.class%',
            'inject_helper' => true,
        ),
    );

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
            $config = $this->getI18nRouterConfig($router->getClass(), $container);

            // Change class
            $router->setClass($config['class']);

            // Inject i18n helper if needed
            if ($config['inject_helper']) {
                $router->addMethodCall('setI18nHelper', array(new Reference('jms_i18n_routing.helper')));
            }
        }
    }

    /**
     * Returns the i18n router config.
     *
     * @param string $class
     * @param ContainerBuilder $container
     * @return array
     */
    private function getI18nRouterConfig($class, ContainerBuilder $container)
    {
        if (preg_match('~^%[a-z0-9-_\.]+%$~i', $class)) {
            if (!$container->hasParameter($class = trim($class, '%'))) {
                throw new RuntimeException(sprintf('Can\'t resolve %s parameter.', $class));
            }
            $class = $container->getParameter($class);
        }
        $reflection = new \ReflectionClass($class);

        foreach ($this->classMap as $base => $config) {
            if ($class === $base || $reflection->isSubclassOf($base)) {
                return $config;
            }
        }

        throw new RuntimeException(sprintf('Can\'t convert %s into a i18n router.', $class));
    }
}
