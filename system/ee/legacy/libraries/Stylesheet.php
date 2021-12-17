<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Library\Resource\Stylesheet;

/**
 * Stylesheet
 */
class EE_Stylesheet extends Stylesheet
{
    public $style_cache = array();

    /**
     * Request CSS Template
     *
     * Handles CSS requests for the standard Template engine
     *
     * @access	public
     * @return	void
     */
    public function request_css_template()
    {
        return $this->request_template();
    }
}
// END CLASS

// EOF
