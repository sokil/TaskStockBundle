<?php

namespace Sokil\TaskStockBundle\Common\Localization;

use Doctrine\Common\Collections\Collection;

interface LocalizedInterface
{
    /**
     *
     * @param string $lang
     * @return LocalizationInterface $localization
     */
    public function getLocalization($lang);

    /**
     * Get localizations
     *
     * @return Collection
     */
    public function getLocalizations();
}