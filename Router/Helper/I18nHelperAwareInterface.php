<?php

namespace JMS\I18nRoutingBundle\Router\Helper;

/**
 * Interface I18nHelperAwareInterface
 * @package JMS\I18nRoutingBundle\Router\Helper
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
interface I18nHelperAwareInterface
{
    /**
     * Sets the helper.
     *
     * @param I18nHelperInterface $helper
     */
    public function setI18nHelper(I18nHelperInterface $helper);

    /**
     * Returns the i18nHelper.
     *
     * @return I18nHelperInterface
     */
    public function getI18nHelper();
}