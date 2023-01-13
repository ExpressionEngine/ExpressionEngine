<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com).
 *
 * @see      https://expressionengine.com/
 *
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Resource;

class Stylesheet extends Request
{
    /**
     * Request CSS Template.
     *
     * Handles CSS requests for the standard Template engine
     */
    public function request_template()
    {
        if ('css/_ee_channel_form_css' == rtrim(ee()->uri->uri_string(), '/')) {
            return $this->_ee_channel_form_css();
        }

        return parent::request_template();
    }

    /**
     * EE Channel:form CSS.
     *
     * Provides basic CSS for channel:form functionality on the frontend
     */
    private function _ee_channel_form_css()
    {
        $files[] = PATH_THEMES . 'cform/css/eecms-cform.min.css';

        $out = '';

        foreach ($files as $file) {
            if (file_exists($file)) {
                $out .= file_get_contents($file);
            }
        }

        $out = str_replace('../../asset/', URL_THEMES_GLOBAL_ASSET, $out);

        $this->_send_resource($out, time());
    }
}
// END CLASS

// EOF
