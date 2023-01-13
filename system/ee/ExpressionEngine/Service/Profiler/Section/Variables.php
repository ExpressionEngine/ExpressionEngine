<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Profiler\Section;

use ExpressionEngine\Service\Profiler\ProfilerSection;

/**
 * Variables Profiler Section
 */
class Variables extends ProfilerSection
{
    /**
     * @var userdata bits that we don't want to display
     */
    protected $skip = array('password', 'salt', 'unique_id', 'session_id', 'fingerprint');

    /**
     * Get a brief text summary (used for tabs, labels, etc.)
     *
     * @return  string  the section summary
     **/
    public function getSummary()
    {
        return lang('profiler_' . $this->section_name);
    }

    /**
     * Gets the view name needed to render the section
     *
     * @return string  the view/name
     **/
    public function getViewName()
    {
        return 'profiler/section/var-list';
    }

    /**
     * Set the section's data
     *
     * @return void
     **/
    public function setData($data)
    {
        extract($data);

        $data['server'] = $this->prepServerData($server);
        $data['cookie'] = $this->prepData($cookie);
        $data['get'] = $this->prepData($get);
        $data['post'] = $this->prepData($post);
        $data['userdata'] = $this->prepData($userdata);

        $this->data = array('performance' => $data);
    }

    private function prepServerData($server)
    {
        $prepped_data = array();

        foreach (array('HTTP_ACCEPT', 'HTTP_USER_AGENT', 'HTTP_CONNECTION', 'SERVER_PORT', 'SERVER_NAME', 'REMOTE_ADDR', 'SERVER_SOFTWARE', 'HTTP_ACCEPT_LANGUAGE', 'SCRIPT_NAME', 'REQUEST_METHOD', 'HTTP_HOST', 'REMOTE_HOST', 'CONTENT_TYPE', 'SERVER_PROTOCOL', 'QUERY_STRING', 'HTTP_ACCEPT_ENCODING', 'HTTP_X_FORWARDED_FOR') as $header) {
            $prepped_data[$header] = (isset($server[$header])) ? htmlspecialchars($server[$header]) : '';
        }

        return $prepped_data;
    }

    private function prepData($data)
    {
        $prepped_data = array();

        foreach ($data as $key => $val) {
            if (in_array($key, $this->skip)) {
                continue;
            }

            $prepped_data[ee('Security/XSS')->clean($key)] = htmlspecialchars(stripslashes(print_r($val, true)));
        }

        return $prepped_data;
    }
}

// EOF
