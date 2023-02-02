<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Template\Variables;

use ExpressionEngine\Service\Template\Variables;

/**
 * Standard Global Variables
 */
class StandardGlobals extends Variables
{
    /**
     * @var object Legacy Template object
     */
    private $legacy_tmpl_obj;

    /**
     * Constructor
     * @param object $legacy_tmpl_obj Legacy Template object
     */
    public function __construct($legacy_tmpl_obj)
    {
        $this->legacy_tmpl_obj = $legacy_tmpl_obj;
        parent::__construct();
    }

    public function getTemplateVariables()
    {
        if (! empty($this->variables)) {
            return $this->variables;
        }

        //  Add in User-defined Global Variables first so that
        //  they can use other standard globals
        $user_globals = ee('Model')->make('GlobalVariable')->loadAll();

        $this->variables = $user_globals->getDictionary('variable_name', 'variable_data');

        $this->variables = $this->variables + [
            'app_build' => APP_BUILD,
            'app_version' => APP_VER,
            'build' => APP_BUILD,
            'captcha' => $this->getCaptcha($this->legacy_tmpl_obj->template),
            'charset' => ee()->config->item('output_charset'),
            'cp_session_id' => (isset(ee()->session) && ee()->session->access_cp === true) ? ee()->session->session_id() : 0,
            'cp_url' => (isset(ee()->session) && ee()->session->access_cp === true) ? ee()->config->item('cp_url') : '',
            'current_path' => (ee()->uri->uri_string) ? str_replace(array('"', "'"), array('%22', '%27'), ee()->uri->uri_string) : '/',
            'current_query_string' => http_build_query($_GET), // GET has been sanitized!
            'current_url' => ee()->functions->fetch_current_uri(),
            'debug_mode' => (ee()->config->item('debug') > 0) ? ee()->lang->line('on') : ee()->lang->line('off'),
            'doc_url' => DOC_URL,
            'gzip_mode' => (ee()->config->item('gzip_output') == 'y') ? ee()->lang->line('enabled') : ee()->lang->line('disabled'),
            'hits' => $this->legacy_tmpl_obj->template_hits,
            'homepage' => ee()->functions->fetch_site_index(),
            'ip_address' => ee()->input->ip_address(),
            'ip_hostname' => ee()->input->ip_address(),
            'is_ajax_request' => AJAX_REQUEST,
            'lang' => ee()->config->item('xml_lang'),
            'last_segment' => ($seg_array = ee()->uri->segment_array()) ? end($seg_array) : '',
            'member_profile_link' => $this->getMemberProfileLink(),
            'password_max_length' => PASSWORD_MAX_LENGTH,
            'site_description' => stripslashes(ee()->config->item('site_description')),
            'site_id' => stripslashes(ee()->config->item('site_id')),
            'site_index' => stripslashes(ee()->config->item('site_index')),
            'site_label' => stripslashes(ee()->config->item('site_label')),
            'site_name' => stripslashes(ee()->config->item('site_name')),
            'site_short_name' => stripslashes(ee()->config->item('site_short_name')),
            'site_url' => stripslashes(ee()->config->item('site_url')),
            'template_group' => $this->legacy_tmpl_obj->group_name,
            'template_group_id' => $this->legacy_tmpl_obj->template_group_id,
            'template_id' => $this->legacy_tmpl_obj->template_id,
            'template_name' => $this->legacy_tmpl_obj->template_name,
            'template_type' => $this->legacy_tmpl_obj->embed_type ?: $this->legacy_tmpl_obj->template_type,
            'theme_folder_url' => URL_THEMES,
            'theme_user_folder_url' => URL_THIRD_THEMES,
            'username_max_length' => USERNAME_MAX_LENGTH,
            'version' => APP_VER,
            'version_identifier' => APP_VER_ID,
            'webmaster_email' => stripslashes(ee()->config->item('webmaster_email')),
        ];

        // add member variables and their aliases
        foreach ($this->legacy_tmpl_obj->getUserVars() as $val) {
            $replace = (isset(ee()->session) && isset(ee()->session->userdata[$val]) && strval(ee()->session->userdata[$val]) != '') ?
                ee()->session->userdata[$val] : '';

            $this->variables[$val] = $replace;
            $this->variables['out_' . $val] = $replace;
            $this->variables['global->' . $val] = $replace;
            $this->variables['logged_in_' . $val] = $replace;
        }

        return $this->variables;
    }

    /**
     * {member_profile_link} legacy global
     *
     * @return string The path to the member profile page or an empty string
     */
    private function getMemberProfileLink()
    {
        if (isset(ee()->session) && ee()->session->userdata('member_id') != 0) {
            $name = (ee()->session->userdata['screen_name'] == '') ? ee()->session->userdata['username'] : ee()->session->userdata['screen_name'];

            $path = "<a href='" . ee()->functions->create_url(ee()->config->item('profile_trigger') . '/' . ee()->session->userdata('member_id')) . "'>" . $name . "</a>";

            return $path;
        }

        return '';
    }

    /**
     * Get Captcha
     * @param  string $str The template portion that contains the captcha variable
     * @return string The Captcha, or an empty string if no captcha var is present, for performance
     */
    private function getCaptcha($str)
    {
        // Fetch CAPTCHA
        if (strpos($str, "{captcha}") !== false) {
            return ee('Captcha')->create();
        }

        return '';
    }
}
// END CLASS

// EOF
