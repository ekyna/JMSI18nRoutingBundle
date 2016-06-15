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

namespace JMS\I18nRoutingBundle\Tests\Router;

use JMS\I18nRoutingBundle\Router\DefaultPatternGenerationStrategy;
use JMS\I18nRoutingBundle\Router\DefaultRouteExclusionStrategy;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use JMS\I18nRoutingBundle\Router\I18nHelper;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\Loader\YamlFileLoader as TranslationLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;
use JMS\I18nRoutingBundle\Router\I18nLoader;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use JMS\I18nRoutingBundle\Router\I18nRouter;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Scope;

class I18nRouterTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $router = $this->getRouter();
        $this->assertEquals('/welcome-on-our-website', $router->generate('welcome'));

        $context = new RequestContext();
        $context->setParameter('_locale', 'en');
        $router->setContext($context);

        $this->assertEquals('/welcome-on-our-website', $router->generate('welcome'));
        $this->assertEquals('/willkommen-auf-unserer-webseite', $router->generate('welcome', array('_locale' => 'de')));
        $this->assertEquals('/welcome-on-our-website', $router->generate('welcome', array('_locale' => 'fr')));

        // test homepage
        $this->assertEquals('/', $router->generate('homepage', array('_locale' => 'en')));
        $this->assertEquals('/', $router->generate('homepage', array('_locale' => 'de')));
    }

    public function testGenerateWithHostMap()
    {
        $router = $this->getRouter();
        $router->getI18nHelper()->setConfig(array(
            'host_map' => array(
                'de' => 'de.host',
                'en' => 'en.host',
                'fr' => 'fr.host',
            ),
        ));

        $this->assertEquals('/welcome-on-our-website', $router->generate('welcome'));
        $this->assertEquals('http://en.host/welcome-on-our-website', $router->generate('welcome', array(), UrlGeneratorInterface::ABSOLUTE_URL));

        $context = new RequestContext();
        $context->setParameter('_locale', 'en');
        $router->setContext($context);

        $this->assertEquals('/welcome-on-our-website', $router->generate('welcome'));
        $this->assertEquals('http://en.host/welcome-on-our-website', $router->generate('welcome', array(), UrlGeneratorInterface::ABSOLUTE_URL));
        $this->assertEquals('http://de.host/willkommen-auf-unserer-webseite', $router->generate('welcome', array('_locale' => 'de')));
        $this->assertEquals('http://de.host/willkommen-auf-unserer-webseite', $router->generate('welcome', array('_locale' => 'de'), UrlGeneratorInterface::ABSOLUTE_URL));
    }

    public function testGenerateDoesUseCorrectHostWhenSchemeChanges()
    {
        $router = $this->getRouter();

        $router->getI18nHelper()->setConfig(array(
            'host_map' => array(
                'en' => 'en.test',
                'de' => 'de.test',
            ),
        ));

        $context = new RequestContext();
        $context->setHost('en.test');
        $context->setScheme('http');
        $context->setParameter('_locale', 'en');
        $router->setContext($context);

        $this->assertEquals('https://en.test/login', $router->generate('login'));
        $this->assertEquals('https://de.test/einloggen', $router->generate('login', array('_locale' => 'de')));
    }

    public function testGenerateDoesNotI18nInternalRoutes()
    {
        $router = $this->getRouter();

        $this->assertEquals('/internal?_locale=de', $router->generate('_internal', array('_locale' => 'de')));
    }

    public function testGenerateWithNonI18nRoute()
    {
        $router = $this->getRouter('routing.yml', new IdentityTranslator(new MessageSelector()));
        $this->assertEquals('/this-is-used-for-checking-login', $router->generate('login_check'));
    }

    public function testMatch()
    {
        $router = $this->getRouter();
        $router->getI18nHelper()->setConfig(array(
            'host_map' => array(
                'en' => 'en.test',
                'de' => 'de.test',
                'fr' => 'fr.test',
            ),
        ));

        $context = new RequestContext('', 'GET', 'en.test');
        $context->setParameter('_locale', 'en');
        $router->setContext($context);

        $this->assertEquals(array('_controller' => 'foo', '_locale' => 'en', '_route' => 'welcome'), $router->match('/welcome-on-our-website'));

        $this->assertEquals(array(
            '_controller' => 'JMS\I18nRoutingBundle\Controller\RedirectController::redirectAction',
            'path'        => '/willkommen-auf-unserer-webseite',
            'host'        => 'de.test',
            'permanent'   => true,
            'scheme'      => 'http',
            'httpPort'    => 80,
            'httpsPort'   => 443,
            '_route'      => 'welcome',
        ), $router->match('/willkommen-auf-unserer-webseite'));
    }

    public function testRouteNotFoundForActiveLocale()
    {
        $router = $this->getNonRedirectingHostMapRouter();
        $context = new RequestContext();
        $context->setParameter('_locale', 'en_US');
        $context->setHost('us.test');
        $router->setContext($context);

        // The route should be available for both en_UK and en_US
        $this->assertEquals(array('_route' => 'news_overview', '_locale' => 'en_US'), $router->match('/news'));

        $context->setParameter('_locale', 'en_UK');
        $context->setHost('uk.test');
        $router->setContext($context);

        // The route should be available for both en_UK and en_US
        $this->assertEquals(array('_route' => 'news_overview', '_locale' => 'en_UK'), $router->match('/news'));

        // Tests whether generating a route to a different locale works
        $this->assertEquals('http://nl.test/nieuws', $router->generate('news_overview', array('_locale' => 'nl_NL')));

        $this->assertEquals(array('_route' => 'english_only', '_locale' => 'en_UK'), $router->match('/english-only'));
    }

    /**
     * Tests whether sublocales are properly translated (en_UK and en_US can use different patterns)
     */
    public function testSubLocaleTranslation()
    {
        // Note that the default is set to en_UK by getDoubleLocaleRouter()
        $router = $this->getNonRedirectingHostMapRouter();
        $context = new RequestContext();
        $context->setParameter('_locale', 'en_US');
        $context->setHost('us.test');
        $router->setContext($context);

        // Test overwrite
        $this->assertEquals(array('_route' => 'sub_locale', '_locale' => 'en_US'), $router->match('/american'));

        $context->setParameter('_locale', 'en_UK');
        $context->setHost('uk.test');
        $router->setContext($context);
        $this->assertEquals(array('_route' => 'enUK_only', '_locale' => 'en_UK'), $router->match('/enUK-only'));
    }

    /**
     * @dataProvider getMatchThrowsExceptionFixtures
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public function testMatchThrowsException($locale, $host, $pattern)
    {
        $router = $this->getNonRedirectingHostMapRouter();
        $context = new RequestContext();
        $context->setParameter('_locale', $locale);
        $context->setHost($host);
        $router->setContext($context);

        $router->match($pattern);
    }

    public function getMatchThrowsExceptionFixtures()
    {
        return array(
            array('en_UK', 'uk.tests', '/nieuws'),
            array('en_UK', 'uk.tests', '/dutch_only'),
            array('en_US', 'us.tests', '/enUK-only'),
            array('en_US', 'us.tests', '/english'),
        );
    }

    /**
     * @dataProvider getGenerateThrowsExceptionFixtures
     * @expectedException \Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateThrowsException($locale, $host, $route)
    {
        $router = $this->getNonRedirectingHostMapRouter();
        $context = new RequestContext();
        $context->setParameter('_locale', $locale);
        $context->setHost($host);
        $router->setContext($context);

        $router->generate($route);
    }

    public function getGenerateThrowsExceptionFixtures()
    {
        return array(
            array('en_UK', 'uk.tests', 'dutch_only'),
            array('en_US', 'us.tests', 'enUK_only'),
        );
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\ResourceNotFoundException
     * @expectedExceptionMessage The route "sub_locale" is not available on the current host "us.test", but only on these hosts "uk.test, nl.test, be.test".
     */
    public function testMatchThrowsResourceNotFoundWhenRouteIsUsedByMultipleLocalesOnDifferentHost()
    {
        $router = $this->getNonRedirectingHostMapRouter();

        $context = $router->getContext();
        $context->setParameter('_locale', 'en_US');

        $router->match('/english');
    }

    /**
     * @expectedException \JMS\I18nRoutingBundle\Exception\NotAcceptableLanguageException
     * @expectedExceptionMessage The requested language "en_US" was not available. Available languages: "en_UK, nl_NL, nl_BE"
     */
    public function testMatchThrowsNotAcceptableLanguageWhenRouteIsUsedByMultipleOtherLocalesOnSameHost()
    {
        $router = $this->getNonRedirectingHostMapRouter();
        $router->getI18nHelper()->setConfig(array(
            'host_map' => array(
                'en_US' => 'foo.com',
                'en_UK' => 'foo.com',
                'nl_NL' => 'nl.test',
                'nl_BE' => 'be.test',
            ),
        ));

        $context = $router->getContext();
        $context->setParameter('_locale', 'en_US');

        $router->match('/english');
    }

    public function testMatchCallsLocaleResolverIfRouteSupportsMultipleLocalesAndContextHasNoLocale()
    {
        $localeResolver = $this->getMock('JMS\I18nRoutingBundle\Router\Resolver\LocaleResolverInterface');

        $router = $this->getRouter('routing.yml', null, $localeResolver);

        $context = $router->getContext();
        $context->setParameter('_locale', null);

        $helper = $router->getI18nHelper();

        $ref = new \ReflectionProperty($helper, 'container');
        $ref->setAccessible(true);
        $container = $ref->getValue($helper);
        $container->addScope(new Scope('request'));
        $container->enterScope('request');
        $container->set('request', $request = Request::create('/'));

        $localeResolver->expects($this->once())
            ->method('resolveLocale')
            ->with($request, array('en', 'de', 'fr'))
            ->will($this->returnValue('de'));

        $params = $router->match('/');
        $this->assertSame('de', $params['_locale']);
    }

    private function getRouter($config = 'routing.yml', $translator = null, $localeResolver = null)
    {
        $container = new Container();
        $container->set('routing.loader', new YamlFileLoader(new FileLocator(__DIR__.'/Fixture')));

        if (null === $translator) {
            $translator = new Translator('en', new MessageSelector());
            $translator->setFallbackLocales(array('en'));
            $translator->addLoader('yml', new TranslationLoader());
            $translator->addResource('yml', __DIR__.'/Fixture/routes.de.yml', 'de', 'routes');
            $translator->addResource('yml', __DIR__.'/Fixture/routes.en.yml', 'en', 'routes');
        }

        $helper = new I18nHelper($container, array(
            'i18n_loader_id' => 'i18n_loader',
            'default_locale' => 'en',
            'locales' => array('en', 'de', 'fr'),
        ));
        $container->set('i18n_loader', new I18nLoader(
            new DefaultRouteExclusionStrategy(),
            new DefaultPatternGenerationStrategy($helper, $translator, sys_get_temp_dir())
        ));

        $router = new I18nRouter($container, $config);
        $router->setI18nHelper($helper);

        if (null !== $localeResolver) {
            $router->getI18nMatcher()->setLocalResolver($localeResolver);
        }

        return $router;
    }

    /**
     * Gets the translator required for checking the DoubleLocale tests (en_UK etc)
     */
    private function getNonRedirectingHostMapRouter($config = 'routing.yml') {
        $container = new Container();
        $container->set('routing.loader', new YamlFileLoader(new FileLocator(__DIR__.'/Fixture')));

        $translator = new Translator('en_UK', new MessageSelector());
        $translator->setFallbackLocales(array('en'));
        $translator->addLoader('yml', new TranslationLoader());
        $translator->addResource('yml', __DIR__.'/Fixture/routes.en_UK.yml', 'en_UK', 'routes');
        $translator->addResource('yml', __DIR__.'/Fixture/routes.en_US.yml', 'en_US', 'routes');
        $translator->addResource('yml', __DIR__.'/Fixture/routes.nl.yml', 'nl', 'routes');
        $translator->addResource('yml', __DIR__.'/Fixture/routes.en.yml', 'en', 'routes');

        $helper = new I18nHelper($container, array(
            'i18n_loader_id' => 'i18n_loader',
            'redirect_to_host' => false,
            'default_locale' => 'en_UK',
            'locales' => array('en_UK', 'en_US', 'nl_NL', 'nl_BE'),
            'host_map' => array(
                'en_UK' => 'uk.test',
                'en_US' => 'us.test',
                'nl_NL' => 'nl.test',
                'nl_BE' => 'be.test',
            ),
        ));
        $container->set('i18n_loader', new I18nLoader(
            new DefaultRouteExclusionStrategy(),
            new DefaultPatternGenerationStrategy($helper, $translator, sys_get_temp_dir())
        ));

        $router = new I18nRouter($container, $config);
        $router->setI18nHelper($helper);

        return $router;
    }
}
