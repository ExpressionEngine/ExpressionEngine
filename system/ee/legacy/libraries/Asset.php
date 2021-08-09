<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Asset
 */
class EE_Asset {
    public $asset_cache = array();
    private $type = 'css';

    /**
     * Request ASSET Template
     *
     * Handles ASSET requests for the standard Template engine
     *
     * @access	public
     * @return	void
     */
    public function request_asset_template() {
        if (in_array(ee()->uri->segment(1), ee()->uri->reserved) && ee()->uri->segment(2) !== false) {
            $asset = ee()->uri->segment(2) . '/' . ee()->uri->segment(3);
        } else if (isset($_GET['css'])) {
            $asset = $_GET['css'];
        } else if (isset($_GET['js'])) {
            $asset = $_GET['js'];
            $this->type = 'js';
        } else  {
            $asset = '';
        }

        if (rtrim($asset, '/') == '_ee_channel_form_css') {
            return $this->_ee_channel_form_css();
        }

        $asset = preg_replace("/\.v\.[0-9]{10}/", '', $asset);  // Remove version info

        if ($asset == '' or strpos($asset, '/') === false) {
            show_404();
        }

        if (! isset($this->asset_cache[$asset])) {
            $ex = explode("/", $asset);

            if (count($ex) != 2) {
                show_404();
            }

            list($group, $name) = $ex;

            if (strpos($group, ':') !== false) {
                // if there's a site, let's get it and redefine $group
                list($site, $group) = explode(':', $group, 2);
            }

            ee()->db->select('templates.template_data, templates.template_name,	templates.edit_date');
            ee()->db->from(array('templates', 'template_groups'));

            ee()->db->where(
                ee()->db->dbprefix('templates') . '.group_id',
                ee()->db->dbprefix('template_groups') . '.group_id',
                false
            );
            ee()->db->where('templates.template_name', $name);
            ee()->db->where('template_groups.group_name', $group);
            ee()->db->where('templates.template_type', $this->type);

            if ( isset($site) ) {
                ee()->db->join('sites', 'sites.site_id = templates.site_id');
                ee()->db->where('sites.site_name', $site);
            } else {
                ee()->db->where('templates.site_id', ee()->config->item('site_id'));
            }

            $query = ee()->db->get();

            if ($query->num_rows() == 0) {
                show_404();
            }

            $row = $query->row_array();

            /** -----------------------------------------
            /**  Retrieve template file if necessary
            /** -----------------------------------------*/
            if (ee()->config->item('save_tmpl_files') == 'y') {
                ee()->load->helper('file');
                $basepath = PATH_TMPL . (
                    isset($site) ? $site : ee()->config->item('site_short_name')
                ) . '/';
                $basepath .= $group . '.group/' . $row['template_name'] . '.' . $this->type;

                $str = read_file($basepath);
                $row['template_data'] = ($str !== false) ? $str : $row['template_data'];
            }

            $this->asset_cache[$asset] = str_replace(LD . 'site_url' . RD, stripslashes(ee()->config->item('site_url')), $row['template_data']);
        }

        $this->_send_asset($this->asset_cache[$asset], $row['edit_date']);
    }

    /**
     * EE Channel:form CSS
     *
     * Provides basic CSS for channel:form functionality on the frontend
     *
     * @return	void
     */
    private function _ee_channel_form_css() {
        $files[] = PATH_THEMES . 'cform/css/eecms-cform.min.css';

        $out = '';

        foreach ($files as $file) {
            if (file_exists($file)) {
                $out .= file_get_contents($file);
            }
        }

        $out = str_replace('../../asset/', URL_THEMES_GLOBAL_ASSET, $out);

        $this->_send_asset($out, time());
    }

    /**
     * Send ASSET
     *
     * Sends ASSET with cache headers
     *
     * @access	public
     * @param	string	asset contents
     * @param	int		Unix timestamp (GMT/UTC) of last modification
     * @return	void
     */
    public function _send_asset($asset, $modified) {
        if (ee()->config->item('send_headers') == 'y') {
            $max_age = 604800;
            $modified_since = ee()->input->server('HTTP_IF_MODIFIED_SINCE');

            // Remove anything after the semicolon

            if ($pos = strrpos($modified_since, ';') !== false) {
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
            @header('Content-Length: ' . strlen($asset));
        }

        if($this->type === 'js') {
            header("Content-type: text/javascript");
        } else {
            header("Content-type: text/css");
        }

        exit($asset);
    }
}
// END CLASS

// EOF
