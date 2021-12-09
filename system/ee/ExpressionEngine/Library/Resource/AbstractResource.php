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

abstract class Request
{
    public $resource_cache = [];
    public $type = '';

    /**
     * Request RESOURCE Template.
     *
     * Handles RESOURCE requests for the standard Template engine
     */
    public function request_resource_template()
    {
        if (in_array(ee()->uri->segment(1), ee()->uri->reserved) && false !== ee()->uri->segment(2)) {
            $resource = ee()->uri->segment(2).'/'.ee()->uri->segment(3);
        } elseif (isset($_GET['css'])) {
            $resource = $_GET['css'];
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

        if (!isset($this->resource_cache[$resource])) {
            $ex = explode('/', $resource);

            if (2 != count($ex)) {
                show_404();
            }

            list($group, $name) = $ex;

            if (false !== strpos($group, ':')) {
                // if there's a site, let's get it and redefine $group
                list($site, $group) = explode(':', $group, 2);
            }

            ee()->db->select('templates.template_data, templates.template_name,	templates.edit_date');
            ee()->db->from(['templates', 'template_groups']);

            ee()->db->where(
                ee()->db->dbprefix('templates').'.group_id',
                ee()->db->dbprefix('template_groups').'.group_id',
                false
            );
            ee()->db->where('templates.template_name', $name);
            ee()->db->where('template_groups.group_name', $group);
            ee()->db->where('templates.template_type', $this->type);

            if (isset($site)) {
                ee()->db->join('sites', 'sites.site_id = templates.site_id');
                ee()->db->where('sites.site_name', $site);
            } else {
                ee()->db->where('templates.site_id', ee()->config->item('site_id'));
            }

            $query = ee()->db->get();

            if (0 == $query->num_rows()) {
                show_404();
            }

            $row = $query->row_array();

            /* -----------------------------------------
             * /**  Retrieve template file if necessary
             * /** -----------------------------------------*/
            if ('y' == ee()->config->item('save_tmpl_files')) {
                ee()->load->helper('file');
                $basepath = PATH_TMPL.(isset($site) ? $site : ee()->config->item('site_short_name')).'/';
                $basepath .= $group.'.group/'.$row['template_name'].'.'.$this->type;

                $str = read_file($basepath);
                $row['template_data'] = (false !== $str) ? $str : $row['template_data'];
                $row['edit_date'] = (false !== $str) ? filemtime($basepath) : $row['edit_date'];
            }

            $this->resource_cache[$resource] = str_replace(LD.'site_url'.RD, stripslashes(ee()->config->item('site_url')), $row['template_data']);
        }

        $this->_send_resource($this->resource_cache[$resource], $row['edit_date']);
    }

    /**
     * Send RESOURCE.
     *
     * Sends RESOURCE with cache headers
     *
     * @param	string	resource contents
     * @param	int		Unix timestamp (GMT/UTC) of last modification
     * @param mixed $resource
     * @param mixed $modified
     */
    public function _send_resource($resource, $modified)
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
            $modified = gmdate('D, d M Y H:i:s', $modified).' GMT';
            $expires = gmdate('D, d M Y H:i:s', time() + $max_age).' GMT';

            ee()->output->set_status_header(200);
            @header("Cache-Control: max-age={$max_age}, must-revalidate");
            @header('Last-Modified: '.$modified);
            @header('Expires: '.$expires);
            @header('Content-Length: '.strlen($resource));
        }

        if ('css' === $this->type) {
            header('Content-type: text/css');
        } elseif ('js' === $this->type) {
            header('Content-type: text/javascript');
        } else {
            header('Content-type: text/'.$this->type);
        }

        exit($resource);
    }
}
// END CLASS

// EOF
