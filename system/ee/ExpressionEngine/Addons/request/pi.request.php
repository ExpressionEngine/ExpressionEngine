<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Request Plugin
 */
class Request
{
    public $return_data;

    /**
     * Get variables from the $_GET array
     *
     * @return mixed [string|false]
     */
    public function get()
    {
        return $this->approvedMethod('get');
    }

    /**
     * Get variables from the $_GET and $_POST arrays
     *
     * @return mixed [string|false]
     */
    public function get_post()
    {
        return $this->approvedMethod('get_post');
    }


    /**
     * Get variables from the $_POST array
     *
     * @return mixed [string|false]
     */
    public function post()
    {
        return $this->approvedMethod('post');
    }

    /**
     * Get variables from the $_COOKIE array
     *
     * @return mixed [string|false]
     */
    public function cookie()
    {
        return $this->approvedMethod('cookie');
    }

    /**
     * Get IP Address of user
     * @return string
     */
    public function ip_address()
    {
        $ip = ee()->input->ip_address();
        $this->return_data = $ip;
        return $ip;
    }

    /**
     * get user agent of user
     * @return mixed [string|false]
     */
    public function user_agent()
    {
        $user_agent = ee()->input->user_agent();
        $this->return_data = $user_agent;
        return $user_agent;
    }

    /**
     * get request header from HTTP call
     * @return mixed [string|false]
     */
    public function request_header()
    {
        return $this->approvedMethod('get_request_header');
    }

    /**
     * DRY method for input class calls with XSS
     * @param  string $method
     * @return [type]         [description]
     */
    private function approvedMethod($method)
    {
        $name = ee()->TMPL->fetch_param('name');

        if (!$name) {
            if (empty(ee()->TMPL->tagdata)) {
                $this->return_data = '';
                return $this->return_data;
            }

            return ee()->TMPL->no_results();
        }

        // We will always want to XSS code, since we will be accessing
        // this from the front end.
        $val = ee()->input->{$method}($name, true);

        if (empty($val)) {
            return ee()->TMPL->no_results();
        }

        if (!empty(ee()->TMPL->tagdata)) {
            $tagdata = ee()->TMPL->tagdata;
        } else {
            $separator = ee()->TMPL->fetch_param('separator', '|');
            ee()->TMPL->tagparams['backspace'] = strlen($separator);
            $tagdata = "{item}" . $separator;
        }

        $vars = [];

        if (is_array($val)) {
            foreach ($val as $item) {
                $vars[]['item'] = $item;
            }
        } else {
            $vars[]['item'] = $val;
        }

        $this->return_data = ee()->TMPL->parse_variables($tagdata, $vars);

        return $this->return_data;
    }
}
// EOF
