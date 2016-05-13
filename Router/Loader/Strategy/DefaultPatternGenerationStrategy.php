<?php

namespace JMS\I18nRoutingBundle\Router\Loader\Strategy;

use JMS\I18nRoutingBundle\Router\Helper\I18nHelperInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Translation\LoggingTranslator;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Route;

/**
 * The default strategy supports 3 different scenarios, and makes use of the
 * Symfony2 Translator Component.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DefaultPatternGenerationStrategy implements PatternGenerationStrategyInterface
{
    const STRATEGY_PREFIX = 'prefix';
    const STRATEGY_PREFIX_EXCEPT_DEFAULT = 'prefix_except_default';
    const STRATEGY_CUSTOM = 'custom';

    /**
     * @var I18nHelperInterface
     */
    private $helper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $cacheDir;

    public function __construct(I18nHelperInterface $helper, TranslatorInterface $translator, $cacheDir)
    {
        $this->helper = $helper;
        $this->translator = $translator;
        $this->cacheDir = $cacheDir;
    }

    /**
     * {@inheritDoc}
     */
    public function generateI18nPatterns($routeName, Route $route)
    {
        $patterns = array();
        $translationDomain = $this->helper->getConfig('catalogue');
        $i18nPaths = $route->getOption('i18n_paths') ?: array();
        foreach ($route->getOption('i18n_locales') ?: $this->helper->getConfig('locales') as $locale) {
            if (array_key_exists($locale, $i18nPaths)) {
                $i18nPattern = $i18nPaths[$locale];
            }
            // Check if translation exists in the translation catalogue to avoid errors being logged by
            // the new LoggingTranslator of Symfony 2.6. However, the LoggingTranslator did not implement
            // the interface until Symfony 2.6.5, so an extra check is needed.
            elseif ($this->translator instanceof TranslatorBagInterface || $this->translator instanceof LoggingTranslator) {
                // Check if route is translated.
                if (!$this->translator->getCatalogue($locale)->has($routeName, $translationDomain)) {
                    // No translation found.
                    $i18nPattern = $route->getPath();
                } else {
                    // Get translation.
                    $i18nPattern = $this->translator->trans($routeName, array(), $translationDomain, $locale);
                }
            } else {
                // if no translation exists, we use the current pattern
                if ($routeName === $i18nPattern = $this->translator->trans($routeName, [], $translationDomain, $locale)) {
                    $i18nPattern = $route->getPath();
                }
            }

            // prefix with locale if requested
            $strategy = $this->helper->getConfig('strategy');
            if (self::STRATEGY_PREFIX === $strategy
                || (self::STRATEGY_PREFIX_EXCEPT_DEFAULT === $strategy && $this->helper->getConfig('default_locale') !== $locale)) {
                $i18nPattern = '/'.$locale.$i18nPattern;
                if (null !== $route->getOption('i18n_prefix')) {
                    $i18nPattern = $route->getOption('i18n_prefix').$i18nPattern;
                }
            }

            $patterns[$i18nPattern][] = $locale;
        }

        return $patterns;
    }

    /**
     * {@inheritDoc}
     */
    public function addResources(RouteCollection $i18nCollection)
    {
        foreach ($this->helper->getConfig('locales') as $locale) {
            if (file_exists($metadata = $this->cacheDir.'/translations/catalogue.'.$locale.'.php.meta')) {
                foreach (unserialize(file_get_contents($metadata)) as $resource) {
                    $i18nCollection->addResource($resource);
                }
            }
        }
    }
}
