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
 * Core Content Types
 */
class EE_content_types
{
    private $_table = 'content_types';

    /**
     * Add a content types
     *
     * Gives the fieldtypes an opportunity to do one-time prep work for
     * new content types.
     *
     * Third parties are responsible for calling this when their module
     * is installed.
     *
     * @param	string	Name of the content type being removed
     * @return	void
     */
    public function register($name)
    {
        if (! $name || $name == 'channel') {
            return;
        }

        $param = compact('name');

        ee()->db->insert($this->_table, $param);

        $this->_notify_fieldtypes('register_content_type', $param);
    }

    /**
     * Remove a content type
     *
     * Tells all fieldtypes that they need to potentially do some
     * cleanup work. The fieldtype is responsible for correctly interpreting
     * this signal. The module is responsible for calling this method when
     * it is uninstalled.
     *
     * @param	string	Name of the content type being removed
     * @return	void
     */
    public function unregister($name)
    {
        if (! $name || $name == 'channel') {
            return;
        }

        $param = compact('name');

        ee()->db->delete($this->_table, $param);

        if (ee()->db->affected_rows()) {
            $this->_notify_fieldtypes('unregister_content_type', $param);
        }
    }

    /**
     * Retrieve a list of all content types
     */
    public function all()
    {
        return array_unique(array_map(
            'array_pop',
            ee()->db->select('name')->get($this->_table)->result_array()
        ));
    }

    /**
     * Notify all fieldtypes of the content type change.
     *
     * @param	string	Name of the function to call ([un]register_content_type)
     * @param	string	Name of the content type being modified
     * @return	void
     */
    private function _notify_fieldtypes($fn, $param)
    {
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');
        $ft_api = ee()->api_channel_fields;

        $fts = $ft_api->fetch_installed_fieldtypes();

        foreach ($fts as $key => $attr) {
            if ($ft_api->setup_handler($key)) {
                if ($ft_api->apply('accepts_content_type', $param)) {
                    $ft_api->apply($fn, $param);
                }
            }
        }
    }
}
