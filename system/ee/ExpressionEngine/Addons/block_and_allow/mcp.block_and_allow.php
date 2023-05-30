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
 * Block and Allow control panel
 */
class Block_and_allow_mcp
{
    public $LB = "\r\n";

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct($switch = true)
    {
        ee()->load->helper('form');
    }

    /**
     * Block and Allow Homepage
     *
     * @access public
     * @return string
     */
    public function index()
    {
        if (! ee()->db->table_exists("blockedlist")) {
            show_error(lang("ref_no_blockedlist_table"));
        }

        if (! ee()->db->table_exists("allowedlist")) {
            show_error(lang("ref_no_allowedlist_table"));
        }

        $allow_write_htaccess = false;
        $htaccess_path = null;

        if (ee('Permission')->isSuperAdmin()) {
            $allow_write_htaccess = true;
            $htaccess_path = ee()->config->item('htaccess_path', '', true);
        }

        $vars = array(
            'allow_write_htaccess' => $allow_write_htaccess,
            'base_url' => ee('CP/URL')->make('addons/settings/block_and_allow/save_htaccess_path'),
            'cp_page_title' => '',
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'hide_top_buttons' => true,
            'sections' => array(
                array(
                    array(
                        'title' => 'add_htaccess_file',
                        'desc' => 'add_htaccess_file_desc',
                        'fields' => array(
                            'htaccess_path' => array(
                                'type' => 'text',
                                'value' => $htaccess_path
                            )
                        )
                    ),
                )
            ),
            'blockedlist_ip' => '',
            'blockedlist_agent' => '',
            'blockedlist_url' => '',
            'allowedlist_ip' => '',
            'allowedlist_agent' => '',
            'allowedlist_url' => ''
        );

        foreach (array('blocked', 'allowed') as $kind) {
            $query = ee()->db->get("{$kind}list");

            if ($query->num_rows() != 0) {
                foreach ($query->result_array() as $row) {
                    $vars["{$kind}list_" . $row["{$kind}list_type"]] = str_replace('|', NL, $row["{$kind}list_value"]);
                }
            }
        }

        return ee('View')->make('block_and_allow:index')->render($vars);
    }

    /**
     * Write .htaccess File
     *
     * @access public
     * @return void
     */
    public function save_htaccess_path()
    {
        if (! ee('Permission')->isSuperAdmin() || ee()->input->get_post('htaccess_path') === false || (ee()->input->get_post('htaccess_path') == '' && ee()->config->item('htaccess_path') === false)) {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
        }

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules('htaccess_path', 'lang:htaccess_path', 'callback__check_path');

        ee()->form_validation->set_error_delimiters('<br /><span class="notice">', '<br />');

        if (ee()->form_validation->run() === false) {
            return $this->index();
        }

        ee()->config->_update_config(array('htaccess_path' => ee()->input->get_post('htaccess_path')));

        if (ee()->input->get_post('htaccess_path') == '' && ! ee()->config->item('htaccess_path')) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('htaccess_path_removed'))
                ->addToBody(lang('htaccess_path_removed_desc'))
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
        }

        $this->write_htaccess(parse_config_variables(ee()->input->get_post('htaccess_path')));

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('htaccess_written_successfully'))
            ->addToBody(lang('htaccess_written_successfully_desc'))
            ->defer();
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
    }

    private function _check_path($str)
    {
        if ($str == '') {
            return true;
        }

        $str = parse_config_variables($str);

        if (! file_exists($str) || ! is_file($str)) {
            ee()->form_validation->set_message('_check_path', lang('invalid_htaccess_path'));

            return false;
        } elseif (! is_writeable(ee()->input->get_post('htaccess_path'))) {
            ee()->form_validation->set_message('_check_path', lang('invalid_htaccess_path'));

            return false;
        }

        return true;
    }

    /**
     * Write .htaccess File
     *
     * @access public
     * @return void
     */
    public function write_htaccess($htaccess_path = '', $return = 'redirect')
    {
        $htaccess_path = ($htaccess_path == '') ? ee()->config->item('htaccess_path') : $htaccess_path;

        if (! ee('Permission')->isSuperAdmin() || $htaccess_path == '') {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
        }

        if (! $fp = @fopen($htaccess_path, FOPEN_READ)) {
            if ($return == 'bool') {
                return false;
            }

            show_error(lang('invalid_htaccess_path'));
        }

        $data = '';
        $filesize = filesize($htaccess_path);

        if ($filesize > 0) {
            flock($fp, LOCK_SH);
            $data = @fread($fp, $filesize);
            flock($fp, LOCK_UN);
            fclose($fp);

            if (preg_match("/##EE Spam Block(.*?)##End EE Spam Block/s", $data, $match)) {
                $data = str_replace($match['0'], '', $data);
            }

            $data = trim($data);
        }

        //  Current Blocked
        $query = ee()->db->get('blockedlist');
        $old['url'] = array();
        $old['agent'] = array();
        $old['ip'] = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $old_values = explode('|', trim($row['blockedlist_value']));
                for ($i = 0, $s = count($old_values); $i < $s; $i++) {
                    if (trim($old_values[$i]) != '') {
                        $old[$row['blockedlist_type']][] = preg_quote($old_values[$i]);
                    }
                }
            }
        }

        //  EE currently uses URLs and IPs
        $urls = '';

        while (count($old['url']) > 0) {
            $urls .= 'SetEnvIfNoCase Referer ".*(' . trim(implode('|', array_slice($old['url'], 0, 50))) . ').*" BadRef' . $this->LB;
            $old['url'] = array_slice($old['url'], 50);
        }

        $ips = '';

        while (count($old['ip']) > 0) {
            $ips .= 'SetEnvIfNoCase REMOTE_ADDR "^(' . trim(implode('|', array_slice($old['ip'], 0, 50))) . ').*" BadIP' . $this->LB;
            $old['ip'] = array_slice($old['ip'], 50);
        }

        $site = parse_url(ee()->config->item('site_url'));

        $domain = (! ee()->config->item('cookie_domain')) ? '' : 'SetEnvIfNoCase Referer ".*(' . preg_quote(ee()->config->item('cookie_domain')) . ').*" GoodHost' . $this->LB;

        $domain .= 'SetEnvIfNoCase Referer "^$" GoodHost' . $this->LB;  // If no referrer, they be safe!

        $host = 'SetEnvIfNoCase Referer ".*(' . preg_quote($site['host']) . ').*" GoodHost' . $this->LB;

        if ($urls != '' || $ips != '') {
            $data .= $this->LB . $this->LB . "##EE Spam Block" . $this->LB
                    . $urls
                    . $ips
                    . $domain
                    . $host
                    . "order deny,allow" . $this->LB
                    . "deny from env=BadRef" . $this->LB
                    . "deny from env=BadIP" . $this->LB
                    . "allow from env=GoodHost" . $this->LB
                    . "##End EE Spam Block" . $this->LB . $this->LB;
        }

        if (! $fp = @fopen($htaccess_path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
            show_error(lang('invalid_htaccess_path'));
        }

        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    /**
     * Update Blockedlist
     *
     * @access public
     * @return void
     */
    public function ee_blockedlist()
    {
        $this->_download_update_list('blocked');
        ee('CP/Alert')->makeInline('lists-form')
            ->asSuccess()
            ->withTitle(lang('lists_updated'))
            ->addToBody(lang('blockedlist_downloaded'))
            ->defer();
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
    }

    /**
     * Update Allowedlist
     *
     * @access public
     * @return void
     */
    public function ee_allowedlist()
    {
        $this->_download_update_list('allowed');
        ee('CP/Alert')->makeInline('lists-form')
            ->asSuccess()
            ->withTitle(lang('lists_updated'))
            ->addToBody(lang('allowedlist_downloaded'))
            ->defer();
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
    }

    public function save_lists()
    {
        $this->update_allowedlist();
        $this->update_blockedlist();
        ee('CP/Alert')->makeInline('lists-form')
            ->asSuccess()
            ->withTitle(lang('lists_updated'))
            ->addToBody(lang('lists_updated_desc'))
            ->defer();
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
    }

    /**
     * Update Blocked list items
     *
     * @access public
     * @return void
     */
    private function update_blockedlist()
    {
        if (! ee()->db->table_exists('blockedlist')) {
            show_error(lang('ref_no_blockedlist_table'));
        }

        // Current blocked
        $query = ee()->db->get('blockedlist');
        $old['url'] = array();
        $old['agent'] = array();
        $old['ip'] = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $old_values = explode('|', $row['blockedlist_value']);
                for ($i = 0; $i < count($old_values); $i++) {
                    $old[$row['blockedlist_type']][] = $old_values[$i];
                }
            }
        }

        // Current allowed
        $query = ee()->db->get('allowedlist');
        $white['url'] = array();
        $white['agent'] = array();
        $white['ip'] = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $white_values = explode('|', $row['allowedlist_value']);
                for ($i = 0; $i < count($white_values); $i++) {
                    if (trim($white_values[$i]) != '') {
                        $white[$row['allowedlist_type']][] = ee()->db->escape_str($white_values[$i]);
                    }
                }
            }
        }

        // Update Blocked list with New Values sans allowed list Matches

        $default = array('blockedlist_ip', 'blockedlist_agent', 'blockedlist_url');
        $modified_channels = array();

        foreach ($default as $val) {
            $type = str_replace('blockedlist_', '', $val);

            if (isset($_POST[$val])) {
                $_POST[$val] = str_replace('[-]', '', $_POST[$val]);
                $_POST[$val] = str_replace('[+]', '', $_POST[$val]);
                $_POST[$val] = trim(stripslashes($_POST[$val]));

                $new_values = explode(NL, strip_tags($_POST[$val]));
            } else {
                continue;
            }

            // Clean out user mistakes; and
            // Clean out Referrers with new additions
            foreach ($new_values as $key => $value) {
                if (trim($value) == "" || trim($value) == NL) {
                    unset($new_values[$key]);
                }

                if ($type == 'ip') {
                    // Collapse IPv6 addresses
                    if (ee()->input->valid_ip($value, 'ipv6')) {
                        $new_values[$key] = inet_ntop(inet_pton($value));
                    }
                }
            }

            sort($new_values);

            $_POST[$val] = implode("|", array_unique($new_values));

            ee()->db->where('blockedlist_type', $type);
            ee()->db->delete('blockedlist');

            $data = array(
                'blockedlist_type' => $type,
                'blockedlist_value' => $_POST[$val]
            );

            ee()->db->insert('blockedlist', $data);
        }
    }

    /**
     * Update Allowed list items
     *
     * @access public
     * @return void
     */
    private function update_allowedlist()
    {
        if (! ee()->db->table_exists('allowedlist')) {
            show_error(lang('ref_no_allowedlist_table'));
        }

        // Current allowed
        $query = ee()->db->get('allowedlist');
        $old['url'] = array();
        $old['agent'] = array();
        $old['ip'] = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $old_values = explode('|', $row['allowedlist_value']);
                for ($i = 0; $i < count($old_values); $i++) {
                    $old[$row['allowedlist_type']][] = $old_values[$i];
                }
            }
        }

        // Update Allowed list with New Values
        $default = array('allowedlist_ip', 'allowedlist_agent', 'allowedlist_url');

        foreach ($default as $val) {
            if (isset($_POST[$val])) {
                $type = str_replace('allowedlist_', '', $val);

                $_POST[$val] = str_replace('[-]', '', $_POST[$val]);
                $_POST[$val] = str_replace('[+]', '', $_POST[$val]);
                $_POST[$val] = trim(stripslashes($_POST[$val]));

                $new_values = explode(NL, strip_tags($_POST[$val]));

                // Clean out user mistakes; and
                // Clean out Whitelists with new additions
                foreach ($new_values as $key => $value) {
                    if (trim($value) == "" || trim($value) == NL) {
                        unset($new_values[$key]);
                    }

                    if ($type == 'ip') {
                        // Collapse IPv6 addresses
                        if (ee()->input->valid_ip($value, 'ipv6')) {
                            $new_values[$key] = inet_ntop(inet_pton($value));
                        }
                    }
                }

                $_POST[$val] = implode("|", $new_values);

                ee()->db->where('allowedlist_type', $type);
                ee()->db->delete('allowedlist');

                $data = array(
                    'allowedlist_type' => $type,
                    'allowedlist_value' => $_POST[$val]
                );

                ee()->db->insert('allowedlist', $data);
            }
        }
    }

    /**
     * Download and update ExpressionEngine.com Blocked- or Allowedlist
     *
     * @access private
     * @return void
     */
    private function _download_update_list($listtype = "blocked")
    {
        if (ee()->input->get('token') != CSRF_TOKEN) {
            show_error(lang('unauthorized_access'));
        }

        $vars['cp_page_title'] = lang('block_and_allow_module_name'); // both blocked and allowed lists share this title

        if (! ee()->db->table_exists("{$listtype}list")) {
            show_error(lang("ref_no_{$listtype}list_table"));
        }

        //  Get Current List from ExpressionEngine.com
        ee()->load->library('xmlrpc');
        //ee()->xmlrpc->debug = true;
        ee()->xmlrpc->server('http://ping.expressionengine.com/index.php', 80);
        $method = ($listtype == 'allowed') ? 'whitelist' : 'blacklist';
        ee()->xmlrpc->method("ExpressionEngine." . $method);

        if (ee()->xmlrpc->send_request() === false) {
            ee('CP/Alert')->makeInline('lists-form')
                ->asIssue()
                ->withTitle(lang("ref_{$listtype}list_irretrievable"))
                ->addToBody(ee()->xmlrpc->display_error())
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/block_and_allow'));
        }

        // Array of our returned info
        $remote_info = ee()->xmlrpc->display_response();

        $new['url'] = (! isset($remote_info['urls']) || strlen($remote_info['urls']) == 0) ? array() : explode('|', $remote_info['urls']);
        $new['agent'] = (! isset($remote_info['agents']) || strlen($remote_info['agents']) == 0) ? array() : explode('|', $remote_info['agents']);
        $new['ip'] = (! isset($remote_info['ips']) || strlen($remote_info['ips']) == 0) ? array() : explode('|', $remote_info['ips']);

        //  Add current list
        $query = ee()->db->get("{$listtype}list");
        $old['url'] = array();
        $old['agent'] = array();
        $old['ip'] = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $old_values = explode('|', $row["{$listtype}list_value"]);
                for ($i = 0; $i < count($old_values); $i++) {
                    $old[$row["{$listtype}list_type"]][] = $old_values[$i];
                }
            }
        }

        //  Current listed
        $query = ee()->db->get('allowedlist');
        $white['url'] = array();
        $white['agent'] = array();
        $white['ip'] = array();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $white_values = explode('|', $row['allowedlist_value']);
                for ($i = 0; $i < count($white_values); $i++) {
                    if (trim($white_values[$i]) != '') {
                        $white[$row['allowedlist_type']][] = $white_values[$i];
                    }
                }
            }
        }

        //  Check for uniqueness and sort
        $new['url'] = array_unique(array_merge($old['url'], $new['url']));
        $new['agent'] = array_unique(array_merge($old['agent'], $new['agent']));
        $new['ip'] = array_unique(array_merge($old['ip'], $new['ip']));
        sort($new['url']);
        sort($new['agent']);
        sort($new['ip']);

        //  Put list info back into database
        ee()->db->truncate("{$listtype}list");

        foreach ($new as $key => $value) {
            $listed_value = implode('|', $value);

            $data = array(
                "{$listtype}list_type" => $key,
                "{$listtype}list_value" => $listed_value
            );

            ee()->db->insert("{$listtype}list", $data);
        }
    }
}
// END CLASS

// EOF
