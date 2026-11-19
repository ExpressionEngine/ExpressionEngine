<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com).
 *
 * @see      https://expressionengine.com/
 *
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
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

        if (! is_string($resource)) {
            show_404();
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

        // The site, template group and template each have to be usable as one part of a cache key
        if (! $this->isValidSiteName($site_name) || ! $this->isValidName($group) || ! $this->isValidName($name)) {
            show_404();
        }

        $cache_path = $this->cacheKey($site_name, $group, $name);

        $cached = ee()->cache->get($cache_path, $this->cache_scope);

        if (! $this->isCachedResource($cached) || $cached['edit_date'] < $template_version) {
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
                    'type' => $this->type,
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
        if (empty($resource)) {
            return ee()->cache->delete(self::CACHE_NAMESPACE, $this->cache_scope);
        }

        $cache_path = $this->_cache_path($resource, $site_id);

        return ($cache_path !== null) ? ee()->cache->delete($cache_path, $this->cache_scope) : false;
    }

    /**
     * Get the cache key for a resource given as `group/name` or `site:group/name`
     *
     * @param string $resource
     * @param int $site_id Site the resource belongs to, when it has no site prefix
     * @return string|null Cache key, or NULL when the resource is not validly named
     */
    private function _cache_path($resource, $site_id = 0)
    {
        if (false === strpos($resource, '/')) {
            throw new \Exception('Invalid resource path provided');
        }

        // Names are trimmed the same way as when a resource is requested
        list($group, $name) = array_map('trim', explode('/', $resource, 2));
        $site_name = '';

        if (false !== strpos($group, ':')) {
            list($site_name, $group) = array_map('trim', explode(':', $group, 2));
        } elseif ($site_id != 0) {
            $site_name = ee()->config->get_cached_site_prefs($site_id)['site_short_name'];
        }

        if (! $this->isValidSiteName($site_name) || ! $this->isValidName($group) || ($name !== '' && ! $this->isValidName($name))) {
            return null;
        }

        // Without a template name the key covers every resource cached for the group
        return $this->cacheKey($site_name, $group, $name);
    }

    /**
     * Build the cache key for a template of a site
     *
     * @param string $site_name Site short name, or an empty string for the current site
     * @param string $group Template group name
     * @param string $name Template name
     * @return string
     */
    private function cacheKey($site_name, $group, $name)
    {
        $site_name = ($site_name !== '') ? $site_name : ee()->config->item('site_short_name');

        return self::CACHE_NAMESPACE . $site_name . '/' . $group . '/' . $name;
    }

    /**
     * Whether a value is named the way a site can be named. An empty value
     * stands for the current site.
     *
     * @param string $site_name
     * @return bool
     */
    private function isValidSiteName($site_name)
    {
        return $site_name === '' || (bool) preg_match('/^[a-z0-9_-]+\z/i', (string) $site_name);
    }

    /**
     * Whether a template group or template name can be used as one part of a
     * cache key. Names are otherwise matched against the saved templates, so
     * any name a template already has keeps working.
     *
     * @param string $name
     * @return bool
     */
    private function isValidName($name)
    {
        if (! is_string($name) || in_array($name, array('', '.', '..'), true)) {
            return false;
        }

        // Well-formed text without separators or control characters
        return preg_match('//u', $name) === 1 && ! preg_match('#[\x00-\x1F\x7F/\\\\:]#', $name);
    }

    /**
     * Whether a cached value is a resource of the requested type as stored by this class
     *
     * @param mixed $cached
     * @return bool
     */
    private function isCachedResource($cached)
    {
        return is_array($cached)
            && isset($cached['type'], $cached['edit_date'], $cached['template_data'])
            && $cached['type'] === $this->type
            && is_numeric($cached['edit_date'])
            && is_string($cached['template_data']);
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
