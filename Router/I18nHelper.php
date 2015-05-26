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

use JMS\I18nRoutingBundle\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class I18nHelper
 * @package JMS\I18nRoutingBundle\Router
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class I18nHelper
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $config;


    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param array              $config
     */
    public function __construct(ContainerInterface $container, array $config)
    {
        $this->container    = $container;

        $this->setConfig($config);
    }

    /**
     * Returns the i18n loader.
     */
    public function getI18nLoader()
    {
        return $this->container->get($this->config['i18n_loader_id']);
    }

    /**
     * Sets the configuration.
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        if (null === $this->config) {
            $this->config = array(
                'i18n_loader_id'   => 'jms_i18n_routing.loader',
                'default_locale'   => 'en',
                'locales'          => array('en'),
                'redirect_to_host' => true,
                'host_map'         => array(),
                'cookie'           => array(
                    'enabled'  => true,
                    'name'     => 'hl',
                    'lifetime' => 31536000,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => false,
                    'httponly' => false,
                ),
            );
        }
        $this->config = array_replace($this->config, $config);
    }

    /**
     * Returns the config.
     *
     * @param string $key
     * @return mixed
     */
    public function getConfig($key = null)
    {
        if (null === $this->config) {
            throw new RuntimeException('Configuration must be set first.');
        }
        if ($key) {
            return $this->config[$key];
        }
        return $this->config;
    }

    /**
     * Returns the request or null if not available.
     *
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        if ($this->container->isScopeActive('request')) {
            return $this->container->get('request');
        }
        return null;
    }
}
