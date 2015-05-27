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

namespace JMS\I18nRoutingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * DI Extension.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class JMSI18nRoutingExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config')));
        $loader->load('services.xml');

        $container->setParameter('jms_i18n_routing.routers_ids', $config['routers']);

        $classes = $config['class'];
        //$this->addClassesToCompile(array_values($classes));

        foreach(array('router', 'dynamic_router', 'chain_router') as $key) {
            $classKey = sprintf('jms_i18n_routing.%s.class', $key);
            if (!$container->has($classKey)) {
                $container->setParameter($classKey, $classes[$key]);
            }
            unset($classes[$key]); // Routers classes are not needed by the helper
        }

        $container
            ->getDefinition('jms_i18n_routing.helper')
            ->replaceArgument(1, array(
                'i18n_loader_id'   => 'jms_i18n_routing.loader',
                'default_locale'   => $config['default_locale'],
                'locales'          => $config['locales'],
                'catalogue'        => $config['catalogue'],
                'strategy'         => $config['strategy'],
                'redirect_to_host' => $config['redirect_to_host'],
                'host_map'         => $config['hosts'],
                'cookie'           => $config['cookie'],
                'class'            => $classes,
            ))
        ;

        if ('prefix' === $config['strategy']) {
            $container
                ->getDefinition('jms_i18n_routing.locale_choosing_listener')
                ->addArgument(new Reference('jms_i18n_routing.helper'))
                ->setPublic(true)
                ->addTag('kernel.event_listener', array('event' => 'kernel.exception', 'priority' => 128))
            ;
        }

        if (!$config['hosts'] && $config['cookie']['enabled']) {
            $container
                ->getDefinition('jms_i18n_routing.cookie_setting_listener')
                ->addArgument(new Reference('jms_i18n_routing.helper'))
                ->setPublic(true)
                ->addTag('kernel.event_listener', array('event' => 'kernel.response', 'priority' => 256))
            ;
        }

        // remove route extractor if JMSTranslationBundle is not enabled to avoid any problems
        $bundles = $container->getParameter('kernel.bundles');
        if (!isset($bundles['JMSTranslationBundle'])) {
            $container->removeDefinition('jms_i18n_routing.route_translation_extractor');
        }
    }

    public function getAlias()
    {
        return 'jms_i18n_routing';
    }
}
