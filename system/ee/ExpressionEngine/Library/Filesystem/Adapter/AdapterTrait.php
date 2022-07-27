<?php

namespace ExpressionEngine\Library\Filesystem\Adapter;

/**
 * Adapter Trait
 *
 * Commonly used functions
 */
trait AdapterTrait
{
    protected $settings;

    /**
     * Support ValidationAware
     */
    public function getValidationData()
    {
        return $this->settings;
    }

    /**
     * Support ValidationAware
     */
    public function getValidationRules()
    {
        return $this->_validation_rules;
    }
}
