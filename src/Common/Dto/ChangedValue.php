<?php

namespace Sokil\TaskStockBundle\Common\Dto;

class ChangedValue
{
    private $oldValue;

    private $newValue;

    public function __construct($oldValue, $newValue)
    {
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    public function getOldValue()
    {
        return $this->oldValue;
    }

    public function getNewValue()
    {
        return $this->newValue;
    }
}