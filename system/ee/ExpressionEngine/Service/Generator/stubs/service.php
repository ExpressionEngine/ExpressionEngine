<?php

namespace {{namespace}};

use ExpressionEngine\Service\Addon\Addon;

/**
 * {{service_name}} Service
 * 
 * @package     {{addon_name}}
 * @author      {{author}}
 * @description {{description}}
 */
class {{service_name}}
{
    /**
     * @var Addon
     */
    protected $addon;

    /**
     * Constructor
     *
     * @param Addon $addon
     */
    public function __construct(Addon $addon)
    {
        $this->addon = $addon;
    }

    /**
     * Example service method
     *
     * @param string $param
     * @return string
     */
    public function exampleMethod($param = '')
    {
        return 'Example: ' . $param;
    }

    /**
     * Get addon information
     *
     * @return array
     */
    public function getAddonInfo()
    {
        return $this->addon->getInfo();
    }
} 