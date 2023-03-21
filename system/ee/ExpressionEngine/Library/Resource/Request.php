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

class Request
{
    const CACHE_NAMESPACE = 'resource_cache/';

    const TYPES = array('css', 'js');

    protected $type = 'plain';
    protected $cache_scope = '';

    public function __construct()
    {
        // When using MSM, we're allowed to share resources among sites
        $this->cache_scope = bool_config_item('multiple_sites_enabled') ? \Cache::GLOBAL_SCOPE : \Cache::LOCAL_SCOPE;
    }

    /**
     * Request RESOURCE Template.
     *
     * Handles RESOURCE requests for the standard Template engine
     */
    public function request_template()
    {
        $template_data = '';
        $edit_date = 0;
        $resource = ''; // with group, like `group/styles`
        $group = '';
        $name = '';
        $site_name = '';
        $template_version = 0;
        $modified_since = (string) ee('Request')->header('IF_MODIFIED_SINCE');

        // requests by trigger segments, the ones without version suffixes
        if (in_array(ee()->uri->segment(1), ee()->uri->reserved) && false !== ee()->uri->segment(2)) {
            $resource = ee()->uri->segment(2) . '/' . ee()->uri->segment(3);
            $this->type = ee()->uri->segment(1);
        } else { // requests query strings, the ones with version suffixes
            foreach (self::TYPES as $type) {
                if (ee('Request')->get($type)) {
                    $resource = ee('Request')->get($type);
                    $this->type = $type;
                    break;
                }
            }
        }

        // Remove anything after the semicolon
        if ($pos = strrpos($modified_since, ';') !== false) {
            $modified_since = substr($modified_since, 0, $pos);
        }

        if ($modified_since = strtotime($modified_since)) {
            $template_version = $modified_since;

            $resource = preg_replace('/\\.v\\.[0-9]{10}/', '', $resource);  // Remove version info
        } else {
            preg_match('/\\.v\\.([0-9]{10})/', $resource, $matches);  // get version info

            if (!empty($matches[0])) {
                $resource = str_replace($matches[0], '', $resource);  // Remove version info
            }

            if (!empty($matches[1])) {
                $template_version = (int) $matches[1];
            }
        }

        if ('' == $resource or false === strpos($resource, '/')) {
            show_404();
        }

        $group_and_name = array_map('trim', explode('/', $resource));

        if (2 != count($group_and_name)) {
            show_404();
        }

        list($group, $name) = $group_and_name;

        $name = $name ?: 'index';

        ee()->load->driver('cache');

        if (false !== strpos($group, ':')) {
            // if there's a site, let's get it and redefine $group
            list($site_name, $group) = array_map('trim', explode(':', $group, 2));
        }

        $cache_path = $this->_cache_path($resource);

        $cached = ee()->cache->get($cache_path, $this->cache_scope);

        if (!$cached || !isset($cached['edit_date']) || $cached['edit_date'] < $template_version) {
            $template = ee('Model')->get('Template')
                ->filter('template_name', $name)
                ->filter('template_type', $this->type)
                ->with('TemplateGroup')->filter('TemplateGroup.group_name', $group);

            $template = !empty($site_name)
                ? $template->with('Site')->filter('Site.site_name', $site_name)
                : $template->filter('site_id', ee()->config->item('site_id'));

            $template = $template
                ->fields('template_data', 'edit_date')
                ->all()
                ->first();

            if (!$template) {
                show_404();
            }

            $template_data = $template->template_data;
            $edit_date = $template->edit_date;

            /* -----------------------------------------
            /**  Retrieve template file if necessary
            /** -----------------------------------------*/
            if (bool_config_item('save_tmpl_files')) {
                ee()->load->helper('file');
                $filepath = PATH_TMPL . (!empty($site_name) ? $site_name : ee()->config->item('site_short_name')) . '/';
                $filepath .= $group . '.group' . '/' . $name . '.' . $this->type;
                if (file_exists($filepath)) {
                    $file_edit_date = filemtime($filepath);

                    if ($file_edit_date > $edit_date) {
                        $str = read_file($filepath);

                        if (false !== $str) {
                            $template_data = $str;
                            $edit_date = $file_edit_date;
                        }
                    }
                }
            }

            // Replace {site_url} in template before caching
            $template_data = str_replace(LD . 'site_url' . RD, stripslashes(ee()->config->item('site_url')), $template_data);

            ee()->cache->save(
                $cache_path,
                array(
                    'edit_date' => $edit_date,
                    'template_data' => $template_data
                ),
                // No TTL, cache lives on till cleared
                0,
                $this->cache_scope
            );
        } else {
            $template_data = $cached['template_data'];
            $edit_date = $cached['edit_date'];
        }

        $this->_send_resource($template_data, $edit_date);
    }

    public function clear_cache($resource = '', $site_id = 0)
    {
        return (!empty($resource))
            ? ee()->cache->delete($this->_cache_path($resource, $site_id), $this->cache_scope)
            : ee()->cache->delete(self::CACHE_NAMESPACE, $this->cache_scope);
    }

    private function _cache_path($resource, $site_id = 0)
    {
        switch (true) {
            case (false === strpos($resource, '/')):
                throw new \Exception('Invalid resource path provided');

            case (false !== strpos($resource, ':')):
                return self::CACHE_NAMESPACE . str_replace(':', '/', $resource);

            case ($site_id != 0):
                return self::CACHE_NAMESPACE . ee()->config->get_cached_site_prefs($site_id)['site_short_name'] . '/' . $resource;

            default:
                return self::CACHE_NAMESPACE . ee()->config->item('site_short_name') . '/' . $resource;
        }
    }

    /**
     * Sends RESOURCE with cache headers
     *
     * @param   string  resource contents
     * @param   int     Unix timestamp (GMT/UTC) of last modification
     */
    protected function _send_resource($data, $modified, $type = '')
    {
        if (!in_array($type, self::TYPES)) {
            $type = $this->type;
        }

        ee()->output->send_cache_headers($modified, 604800, null);

        @header('Content-Length: ' . strlen($data));

        if ('css' === $type) {
            @header('Content-type: text/css');
        } elseif ('js' === $type) {
            @header('Content-type: text/javascript');
        } else {
            @header('Content-type: text/' . $type);
        }

        exit($data);
    }
}
// END CLASS

// EOF
