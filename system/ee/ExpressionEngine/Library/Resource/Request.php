<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com).
 *
 * @see      https://expressionengine.com/
 *
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Resource;

class Request
{
    const CACHE_NAMESPACE = 'Resource/';

    protected $type = '';

    /**
     * Request RESOURCE Template.
     *
     * Handles RESOURCE requests for the standard Template engine
     */
    public function request_template()
    {
        $template_data = '';
        $edit_date = 0;

        if (in_array(ee()->uri->segment(1), ee()->uri->reserved) && false !== ee()->uri->segment(2)) {
            $resource = ee()->uri->segment(2) . '/' . ee()->uri->segment(3);
            $this->type = ee()->uri->segment(1);
        } elseif (isset($_GET['css'])) {
            $resource = $_GET['css'];
            $this->type = 'css';
        } elseif (isset($_GET['js'])) {
            $resource = $_GET['js'];
            $this->type = 'js';
        } else {
            $resource = '';
        }

        $resource = preg_replace('/\\.v\\.[0-9]{10}/', '', $resource);  // Remove version info

        if ('' == $resource or false === strpos($resource, '/')) {
            show_404();
        }

        $group_and_name = explode('/', $resource);

        if (2 != count($group_and_name)) {
            show_404();
        }

        list($group, $name) = $group_and_name;

        $name = $name ?: 'index';

        ee()->load->driver('cache');

        if (false !== strpos($group, ':')) {
            // if there's a site, let's get it and redefine $group
            list($site, $group) = explode(':', $group, 2);
        }

        $cached = isset($site)
            // In case the call specifies a website, we are using MSM and
            // sharing resources
            ? ee()->cache->get(self::CACHE_NAMESPACE . md5($resource), \Cache::GLOBAL_SCOPE)
            : ee()->cache->get(self::CACHE_NAMESPACE . md5($resource));

        if (!$cached) {
            $template = ee('Model')->get('Template')
                ->filter('template_name', $name)
                ->filter('template_type', $this->type)
                ->with('TemplateGroup')->filter('TemplateGroup.group_name', $group);

            $template = isset($site)
                ? $template->with('Site')->filter('Site.site_name', $site)
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
        } else {
            $template_data = $cached['template_data'];
            $edit_date = $cached['edit_date'];
        }

        /* -----------------------------------------
		* /**  Retrieve template file if necessary
		* /** -----------------------------------------*/
        if (bool_config_item('save_tmpl_files')) {
            ee()->load->helper('file');
            $filepath = PATH_TMPL . (isset($site) ? $site : ee()->config->item('site_short_name')) . '/';
            $filepath .= $group . '.group/' . $name . '.' . $this->type;
            $file_edit_date = filemtime($filepath);

            if ($file_edit_date < $edit_date) {
                $str = read_file($filepath);

                if (false !== $str) {
                    $template_data = $str;
                    $edit_date = $file_edit_date;
                }
            }
        }

        if (isset($site)) {
            // No TTL, cache lives on till cleared
            ee()->cache->save(
                self::CACHE_NAMESPACE . md5($resource),
                array(
                    'edit_date' => $edit_date,
                    'template_data' => str_replace(LD . 'site_url' . RD, stripslashes(ee()->config->item('site_url')), $template_data)
                ),
                0,
                \Cache::GLOBAL_SCOPE
            );
        } else {
            // No TTL, cache lives on till cleared
            ee()->cache->save(
                self::CACHE_NAMESPACE . md5($resource),
                array(
                    'edit_date' => $edit_date,
                    'template_data' => str_replace(LD . 'site_url' . RD, stripslashes(ee()->config->item('site_url')), $template_data)
                ),
                0,
                \Cache::LOCAL_SCOPE
            );
        }

        $this->_send_resource($template_data, $edit_date);
    }

    /**
     * Send RESOURCE.
     *
     * Sends RESOURCE with cache headers
     *
     * @param	string	resource contents
     * @param	int		Unix timestamp (GMT/UTC) of last modification
     * @param mixed $data
     * @param mixed $modified
     */
    protected function _send_resource($data, $modified)
    {
        if ('y' == ee()->config->item('send_headers')) {
            $max_age = 604800;
            $modified_since = ee()->input->server('HTTP_IF_MODIFIED_SINCE');

            // Remove anything after the semicolon

            if ($pos = false !== strrpos($modified_since, ';')) {
                $modified_since = substr($modified_since, 0, $pos);
            }

            // If the file is in the client cache, we'll
            // send a 304 and be done with it.

            if ($modified_since && (strtotime($modified_since) == $modified)) {
                ee()->output->set_status_header(304);

                exit;
            }

            // All times GMT
            $modified = gmdate('D, d M Y H:i:s', $modified) . ' GMT';
            $expires = gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT';

            ee()->output->set_status_header(200);
            @header("Cache-Control: max-age={$max_age}, must-revalidate");
            @header('Last-Modified: ' . $modified);
            @header('Expires: ' . $expires);
            @header('Content-Length: ' . strlen($data));
        }

        if ('css' === $this->type) {
            header('Content-type: text/css');
        } elseif ('js' === $this->type) {
            header('Content-type: text/javascript');
        } else {
            header('Content-type: text/' . $this->type);
        }

        exit($data);
    }
}
// END CLASS

// EOF
