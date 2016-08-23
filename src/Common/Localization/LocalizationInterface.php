<?php

namespace Sokil\TaskStockBundle\Common\Localization;

interface LocalizationInterface
{
    /**
     * Set lang
     *
     * @param string $lang
     */
    public function setLang($lang);

    /**
     * Get lang
     *
     * @return string
     */
    public function getLang();
}