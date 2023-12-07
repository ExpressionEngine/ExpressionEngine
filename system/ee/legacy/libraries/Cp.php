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
 * CP
 */
class Cp
{
    private $view;

    protected $its_all_in_your_head = array();
    protected $footer_item = array();

    public $cp_theme_url = ''; // base URL to the CP theme folder
    public $installed_modules = false;
    public $requests = array();
    public $loaded = array();

    public $js_files = array(
        'ui' => array(),
        'plugin' => array(),
        'file' => array(),
        'package' => array(),
        'fp_module' => array()
    );

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        if (ee()->router->fetch_class() == 'ee') {
            show_error("The CP library is only available on Control Panel requests.");
        }

        // Cannot set these in the installer
        if (! defined('EE_APPPATH')) {
            $this->cp_theme_url = URL_THEMES . 'cp/';

            ee()->load->vars(array(
                'cp_theme_url' => $this->cp_theme_url
            ));
        }

        // Make sure all requests to iframe the CP are denied
        ee()->output->set_header('X-Frame-Options: SAMEORIGIN');
    }

    /**
     * Set Certain Default Control Panel View Variables
     *
     * @return void
     */
    public function set_default_view_variables()
    {
        $langfile = substr(ee()->router->class, 0, strcspn(ee()->router->class, '_'));

        // Javascript Path Constants
        define('PATH_JQUERY', PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/jquery/');
        define('JS_FOLDER', PATH_JS);

        ee()->load->library('javascript', array('autoload' => false));

        ee()->lang->loadfile($langfile, '', false);

        // Meta-refresh tag
        if ($refresh = ee()->session->flashdata('meta-refresh')) {
            ee()->view->set_refresh($refresh['url'], $refresh['rate']);
        }

        $cp_table_template = array(
            'table_open' => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">'
        );

        $cp_pad_table_template = array(
            'table_open' => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">'
        );

        if (ee()->session->userdata('member_id') == 0) {
            $member = ee('Model')->make('Member');
        } else {
            $member = ee()->session->getMember();
        }

        $notepad_content = ($member->notepad) ?: '';

        // Global view variables
        $vars = array(
            'cp_homepage_url' => $member->getCPHomepageURL(),
            'cp_page_onload' => '',
            'cp_page_title' => '',
            'cp_breadcrumbs' => array(),
            'cp_right_nav' => array(),
            'cp_messages' => array(),
            'cp_notepad_content' => $notepad_content,
            'cp_table_template' => $cp_table_template,
            'cp_pad_table_template' => $cp_pad_table_template,
            'cp_theme_url' => $this->cp_theme_url,
            'cp_current_site_label' => ee()->config->item('site_name'),
            'cp_screen_name' => ee('Format')->make('Text', $member->screen_name)->attributeSafe(),
            'cp_member_primary_role_title' => $member->PrimaryRole ? $member->PrimaryRole->name : '',
            'cp_avatar_path' => ($member->avatar_filename) ? ee()->config->slash_item('avatar_url') . $member->avatar_filename : (URL_THEMES . 'asset/img/default-avatar.png'),
            'cp_avatar_width' => ($member->avatar_filename) ? $member->avatar_width : '',
            'cp_avatar_height' => ($member->avatar_filename) ? $member->avatar_height : '',
            'cp_quicklinks' => $this->_get_quicklinks($member->getQuicklinks()),

            'EE_view_disable' => false,
            'is_super_admin' => (ee('Permission')->isSuperAdmin()) ? true : false, // for conditional use in view files
        );

        // global table data
        ee()->session->set_cache('table', 'cp_template', $cp_table_template);
        ee()->session->set_cache('table', 'cp_pad_template', $cp_pad_table_template);

        // we need these paths again in my account, so we'll keep track of them
        // kind of hacky, but before it was accessing _ci_cache_vars, which is worse

        ee()->session->set_cache('cp_sidebar', 'cp_avatar_path', $vars['cp_avatar_path'])
            ->set_cache('cp_sidebar', 'cp_avatar_width', $vars['cp_avatar_width'])
            ->set_cache('cp_sidebar', 'cp_avatar_height', $vars['cp_avatar_height']);

        // The base javascript variables that will be available globally through EE.varname
        // this really could be made easier - ideally it would show up right below the main
        // jQuery script tag - before the plugins, so that it has access to jQuery.

        // If you use it in your js, please uniquely identify your variables - or create
        // another object literal:
        // Bad: EE.test = "foo";
        // Good: EE.unique_foo = "bar"; EE.unique = { foo : "bar"};

        $js_lang_keys = array(
            'logout' => lang('logout'),
            'search' => lang('search'),
            'session_idle' => lang('session_idle'),
            'btn_fix_errors' => lang('btn_fix_errors'),
            'btn_fix_errors' => lang('btn_fix_errors'),
            'check_all' => lang('check_all'),
            'clear_all' => lang('clear_all'),
            'keyword_search' => lang('keyword_search'),
            'loading' => lang('loading'),
            'searching' => lang('searching'),
            'dark_theme' => lang('dark_theme'),
            'light_theme' => lang('light_theme'),
            'slate_theme' => lang('slate_theme'),
            'snow_theme' => lang('snow_theme'),
            'many_jump_results' => lang('many_jump_results'),
            'password_icon' => lang('password_icon')
        );

        $lastUpdateCheck = false;
        $statusCache = ee()->cache->get_metadata('/addons-status');
        if (!empty($statusCache) && !empty($statusCache['mtime'])) {
            $lastUpdateCheck = time() - $statusCache['mtime'];
        }

        ee()->javascript->set_global(array(
            'BASE' => str_replace(AMP, '&', BASE),
            'XID' => CSRF_TOKEN,
            'CSRF_TOKEN' => CSRF_TOKEN,
            'PATH_CP_GBL_IMG' => PATH_CP_GBL_IMG,
            'CP_SIDEBAR_STATE' => ee()->session->userdata('show_sidebar'),
            'username' => ee()->session->userdata('username'),
            'router_class' => ee()->router->class, // advanced css
            'lang' => $js_lang_keys,
            'THEME_URL' => $this->cp_theme_url,
            'hasRememberMe' => (bool) ee()->remember->exists(),
            'cp.updateCheckURL' => ee('CP/URL', 'settings/general/version-check')->compile(),
            'cp.accessResponseURL' => ee('CP/URL', 'license/handleAccessResponse')->compile(),
            'cp.lastUpdateCheck' => $lastUpdateCheck,
            'site_id' => ee()->config->item('site_id'),
            'site_name' => ee()->config->item('site_name'),
            'site_url' => ee()->config->item('site_url'),
            'cp.collapseNavURL' => ee('CP/URL', 'homepage/toggle-sidebar-nav')->compile(),
            'cp.dismissBannerURL' => ee('CP/URL', 'homepage/dismiss-banner')->compile(),
            'cp.collapseSecondaryNavURL' => ee('CP/URL', 'homepage/toggle-secondary-sidebar-nav')->compile(),
            'fileManagerCompatibilityMode' => bool_config_item('file_manager_compatibility_mode'),
        ));

        if (ee()->session->flashdata('update:completed')) {
            $updateCompletedScript = "
                $(document).ready(function() {
                    document.getElementsByClassName('js-about')[0].click();
                    $('html, body').animate({
                        scrollTop: $('.app-about').offset().top
                    }, 500);
                });
            ";
            ee()->javascript->output($updateCompletedScript);
        }

        if (ee()->session->flashdata('update:show_status_switch')) {
            $systemStatusAlert = ee('CP/Alert')->makeBanner('warning_system_status')
                ->asAttention()
                ->canClose()
                ->withTitle(lang('warning_system_status_title'))
                ->addToBody(sprintf(
                    lang('warning_system_status_message'),
                    (ee()->config->item('is_system_on') == 'y') ? lang('online') : lang('offline'),
                    ee('CP/URL')->make('settings/general')
                ));

            $button = form_open(
                ee('CP/URL')->make('settings/config'),
                '',
                array(
                    'is_system_on' => (ee()->config->item('is_system_on') == 'y') ? 'n' : 'y'
                )
            );

            $button .= '<input class="button button--primary" type="submit" value="' . sprintf(lang('warning_system_status_button'), (ee()->config->item('is_system_on') == 'y') ? lang('offline') : lang('online')) . '">';
            $button .= form_close();

            $systemStatusAlert->addToBody($button, '', false);
            $systemStatusAlert->now();
        }

        // Combo-load the javascript files we need for every request

        $js_scripts = array(
            'ui' => array('core', 'widget', 'mouse', 'position', 'sortable', 'dialog', 'button'),
            'plugin' => array('ee_interact.event', 'ee_broadcast.event', 'ee_notice', 'ee_txtarea', 'tablesorter', 'ee_toggle_all', 'nestable'),
            'file' => array('vendor/react/react.min', 'vendor/react/react-dom.min', 'vendor/popper', 'vendor/focus-visible',
                'vendor/underscore', 'cp/global_start', 'cp/form_validation', 'cp/sort_helper', 'cp/form_group',
                'bootstrap/dropdown-controller', 'cp/modal_form', 'cp/confirm_remove', 'cp/fuzzy_filters', 'cp/jump_menu',
                'components/no_results', 'components/loading', 'components/filters', 'components/dropdown_button',
                'components/filterable', 'components/toggle', 'components/select_list',
                'fields/select/select', 'fields/select/mutable_select', 'fields/dropdown/dropdown', 'cp/passwords')
        );
        $installed_modules = ee()->db->select('module_name,module_version')->get('modules');

        $installed_modules_js = [];
        foreach ($installed_modules->result() as $installed_module) {
            $installed_modules_js[strtolower($installed_module->module_name)] = [
                'slug' => strtolower($installed_module->module_name),
                'name' => ucwords(str_replace('_', ' ', $installed_module->module_name)),
                'version' => $installed_module->module_version,
            ];
        }

        // Grab the site's license key, license URL, and list of installed
        // add-ons to send to the license validation service.
        ee()->javascript->set_global(array(
            'cp.appVer' => APP_VER,
            'cp.licenseKey' => ee()->config->item('site_license_key'),
            'cp.lvUrl' => 'https://updates.expressionengine.com/check',
            'cp.usesPro' => ee('pro:Access')->requiresValidLicense(),
            'cp.installedAddons' => json_encode($installed_modules_js)
        ));

        $modal = ee('View')->make('ee:_shared/modal_confirm_remove')->render([
            'name' => 'modal-default-confirm-remove',
            'form_url' => '#',
            'hidden' => [
                'bulk_action' => 'remove',
                'content_id' => ''
            ]
        ]);
        ee('CP/Modal')->addModal('default-remove', $modal);

        $this->add_js_script($js_scripts);
        $this->_seal_combo_loader();

        foreach ($vars as $key => $value) {
            ee()->view->$key = $value;
        }

        ee()->load->vars($vars);
    }

    /**
     * Render output (html)
     *
     * @access public
     * @return void
     */
    public function render($view, $data = array(), $return = false)
    {
        $date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));

        ee()->load->helper('text');
        ee()->load->library('el_pings');

        if (ee()->config->item('new_version_check') == 'y' && $new_version = ee()->el_pings->getUpgradeInfo()) {
            ee()->view->new_version = $new_version;
            $version_major = explode('.', APP_VER, 2)[0];
            $update_version_major = explode('.', $new_version['version'], 2)[0];

            if (version_compare($version_major, $update_version_major, '<')) {
                ee()->view->major_update = true;
            }
        }

        ee()->view->pro_license_status = '';

        $pro_status = !ee('pro:Access')->requiresValidLicense() ? 'skip' : (string) ee('Addon')->get('pro')->checkCachedLicenseResponse();
        switch ($pro_status) {
            case 'update_available':
                ee()->view->pro_license_status = 'valid';

                break;

            case '':
                ee()->view->pro_license_status = 'na';

                break;

            default:
                ee()->view->pro_license_status = $pro_status;

                break;
        }

        $this->_notices();

        ee()->view->formatted_version = formatted_version(APP_VER);

        $data['_extra_library_src'] = implode('', ee()->jquery->jquery_code_for_load);

        // add global end file
        $this->_seal_combo_loader();
        $this->add_js_script('file', 'cp/global_end');

        ee()->view->ee_build_date = ee()->localize->format_date($date_format, ee()->localize->parse_build_date(), true);
        ee()->view->version_identifier = APP_VER_ID;
        ee()->view->show_news_button = $this->shouldShowNewsButton();

        ee()->view->ee_license = ee('License')->getEELicense();
        $sidebar = ee('CP/Sidebar')->render();

        if (! empty($sidebar)) {
            ee()->view->left_nav = $sidebar;
            ee()->view->left_nav_collapsed = ee('CP/Sidebar')->collapsedState;
        }

        return ee()->view->render($view, $data, $return);
    }

    /**
     * Determines whether or not the news button beside the version number
     * should be shown
     *
     * @return boolean
     */
    protected function shouldShowNewsButton()
    {
        $news_view = ee('Model')->get('MemberNewsView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->first();

        return (! $news_view or version_compare(APP_VER, $news_view->version, '>'));
    }

    /**
     * Load up the menu for our view
     *
     * @access public
     * @return void
     */
    protected function _menu()
    {
        if (ee()->view->disabled('ee_menu')) {
            return;
        }

        ee()->view->cp_main_menu = ee()->menu->generate_menu();
    }

    /**
     * Run a number of checks/tests and display any notices
     *
     * @return void
     */
    protected function _notices()
    {
        $alert = $this->_checksum_bootstrap_files();

        // These are only displayed to Super Admins
        if (! ee('Permission')->isSuperAdmin()) {
            return;
        }

        $notices = array();

        // Show a notice if the cache folder is not writeable
        if (! ee()->cache->file->is_supported()) {
            $notices[] = lang('unwritable_cache_folder');
        }

        // Show a notice if the config file is not writeable
        if (! is_really_writable(ee()->config->config_path)) {
            $notices[] = lang('unwritable_config_file');
        }

        // Check to see if the config file matches the Core version constant
        if (APP_VER !== ee()->config->item('app_version')) {
            $notices[] = sprintf(lang('version_mismatch'), ee()->config->item('app_version'), APP_VER);
        }

        if (!is_dir(PATH_THEMES) || !is_dir(PATH_THEMES_GLOBAL_ASSET)) {
            $notices[] = sprintf(lang('theme_folder_wrong'), ee('CP/URL', '/cp/settings/urls'));
        }

        if (! ee()->config->item('session_crypt_key')) {
            $notices[] = sprintf(
                lang('missing_encryption_key'),
                'session_crypt_key',
                DOC_URL . 'troubleshooting/error-messages.html#missing-encryption-keys'
            );
        }

        if (! ee()->config->item('encryption_key')) {
            $notices[] = sprintf(
                lang('missing_encryption_key'),
                'encryption_key',
                DOC_URL . 'troubleshooting/error-messages.html#missing-encryption-keys'
            );
        }

        if (defined('ELLISLAB_STILL_HERE') && ELLISLAB_STILL_HERE == true) {
            $notices[] = sprintf(
                lang('el_folder_present'),
                SYSDIR . '/ee/EllisLab',
                DOC_URL . 'installation/updating.html#if-updating-from-expressionengine-3-or-higher'
            );
        }

        //do they need to accept consent?
        if (bool_config_item('require_cookie_consent')) {
            $consentRequest = ee('Model')->get('ConsentRequest')->filter('consent_name', 'ee:cookies_functionality')->first();
            if (!empty($consentRequest)) {
                $consent = ee()->session->getMember()->Consents->filter('consent_request_id', $consentRequest->getId())->first();
                if (empty($consent) || !$consent->isGranted()) {
                    $notices[] = sprintf(lang('cookies_functionality_consent_required'), ee('CP/URL', 'members/profile/consent'));
                }
            }
        }

        if (! empty($notices)) {
            if (! $alert) {
                $alert = ee('CP/Alert')->makeBanner('notices')
                    ->asWarning()
                    ->withTitle(lang('cp_message_warn'))
                    ->now();
            } else {
                $alert->addSeparator();
            }

            $last = end($notices);
            reset($notices);
            foreach ($notices as $notice) {
                $alert->addToBody($notice);

                if ($notice != $last) {
                    $alert->addSeparator();
                }
            }
        }
    }

    /**
     * Bootstrap Checksum Validation
     *
     * Creates a checksum for our bootstrap files and checks their
     * validity with the database
     *
     * @return Alert|null NULL if everything is alright, otherwise an Alert object
     */
    protected function _checksum_bootstrap_files()
    {
        // Checksum Validation
        ee()->load->library('file_integrity');
        $changed = ee()->file_integrity->check_bootstrap_files();
        $checksum_alert = null;

        if ($changed) {
            // Email the webmaster - if he isn't already looking at the message
            if (ee()->session->userdata('email') != ee()->config->item('webmaster_email')) {
                ee()->file_integrity->send_site_admin_warning($changed);
            }

            if (ee('Permission')->isSuperAdmin()) {
                $alert = ee('CP/Alert')->makeStandard('notices')
                    ->asWarning()
                    ->withTitle(lang('cp_message_warn'))
                    ->addToBody(lang('checksum_changed_warning'))
                    ->addToBody($changed);

                $button = form_open(
                    ee('CP/URL')->make('homepage/accept_checksums'),
                    '',
                    array(
                        'return' => ee('CP/URL')->getCurrentUrl()->encode()
                    )
                );

                $button .= '<input class="button button--primary" type="submit" value="' . lang('checksum_changed_accept') . '">';
                $button .= form_close();

                $alert->addToBody($button, '', false);

                return $alert->now();
            }
        }

        return null;
    }

    /**
     * EE Version Check function
     *
     * Requests a file from ExpressionEngine.com that informs us what the current available version
     * of ExpressionEngine.
     *
     * @return bool|string
     */
    protected function _version_check()
    {
        ee()->load->library('el_pings');
        $version_file = ee()->el_pings->get_version_info();

        if (! $version_file) {
            ee('CP/Alert')->makeBanner('notices')
                ->asWarning()
                ->withTitle(lang('cp_message_warn'))
                ->addToBody(sprintf(
                    lang('new_version_error'),
                    ee()->cp->masked_url(DOC_URL . 'troubleshooting/error-messages.html#cannot-fetch-current-expressionengine-version')
                ))
                ->now();
        }
    }

    /**
     * Mask URL.
     *
     * To be used to create url's that "mask" the real location of the
     * users control panel.  Eg:  http://example.com/index.php?URL=http://example2.com
     *
     * @param string   URL
     * @return string  Masked URL
     */
    public function masked_url($url)
    {
        return ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'URL=' . urlencode($url);
    }

    /**
     * Add JS Script
     *
     * Adds a javascript file to the javascript combo loader
     *
     * @param array - associative array of
     */
    public function add_js_script($script = array(), $in_footer = true)
    {
        if (! is_array($script)) {
            if (is_bool($in_footer)) {
                return false;
            }

            $script = array($script => $in_footer);
            $in_footer = true;
        }

        if (! $in_footer) {
            return $this->its_all_in_your_head = array_merge($this->its_all_in_your_head, $script);
        }

        foreach ($script as $type => $file) {
            if (! is_array($file)) {
                $file = array($file);
            }

            if (array_key_exists($type, $this->js_files)) {
                $this->js_files[$type] = array_merge($this->js_files[$type], $file);
            } else {
                $this->js_files[$type] = $file;
            }
        }

        return $this->js_files;
    }

    /**
     * Render Footer Javascript
     *
     * @param bool Whether to include 'common.js' automatically
     *
     * @return string
     */
    public function render_footer_js($include_common = true)
    {
        $str = '';
        $requests = $this->_seal_combo_loader($include_common);

        foreach ($requests as $req) {
            $str .= '<script type="text/javascript" charset="utf-8" src="' . BASE . AMP . 'C=javascript' . AMP . 'M=combo_load' . $req . '"></script>';
        }

        if (ee()->extensions->active_hook('cp_js_end') === true) {
            $str .= '<script type="text/javascript" src="' . BASE . AMP . 'C=javascript' . AMP . 'M=load' . AMP . 'file=ext_scripts"></script>';
        }

        return $str;
    }

    /**
     * Seal the current combo loader and reopen a new one.
     *
     * @param bool Whether to include 'common.js' automatically
     * @access private
     * @return array
     */
    public function _seal_combo_loader($include_common = true)
    {
        $str = '';
        $mtimes = array();

        if ($include_common) {
            $this->add_js_script([
                'file' => [
                    'common'
                ]
            ]);
        }

        $this->js_files = array_map('array_unique', $this->js_files);

        foreach ($this->js_files as $type => $files) {
            if (isset($this->loaded[$type])) {
                $files = array_diff($files, $this->loaded[$type]);
            }

            if (count($files)) {
                $mtimes[] = $this->_get_js_mtime($type, $files);
                $str .= AMP . $type . '=' . implode(',', $files);
            }
        }

        if ($str) {
            $this->loaded = array_merge_recursive($this->loaded, $this->js_files);

            $this->js_files = array(
                'ui' => array(),
                'plugin' => array(),
                'file' => array(),
                'package' => array(),
                'fp_module' => array(),
                'pro_file' => array(),
                'template' => array()
            );

            $this->requests[] = $str . AMP . 'v=' . max($mtimes);
        }

        return $this->requests;
    }

    /**
     * Get last modification time of a js file.
     * Returns highest if passed an array.
     *
     * @param string
     * @param mixed
     * @return int
     */
    public function _get_js_mtime($type, $name)
    {
        if (is_array($name)) {
            $mtimes = array();

            foreach ($name as $file) {
                $mtimes[] = $this->_get_js_mtime($type, $file);
            }

            return max($mtimes);
        }

        switch ($type) {
            case 'ui':
                $file = PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/jquery/ui/jquery.ui.' . $name . '.js';

                break;

            case 'plugin':
                $file = PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/jquery/plugins/' . $name . '.js';

                break;

            case 'file':
                $file = PATH_JAVASCRIPT_BUILD . $name . '.js';
                if (file_exists($file)) {
                    return filemtime($file);
                }
                $file = PATH_THEMES_GLOBAL_ASSET . 'javascript/' . PATH_JS . '/' . $name . '.js';

                break;

            case 'pro_file':
                $file = PATH_PRO_THEMES . 'js/' . $name . '.js';

                break;

            case 'package':
                if (strpos($name, ':') !== false) {
                    list($package, $name) = explode(':', $name);
                } else {
                    $package = $name;
                }

                $file = PATH_THIRD . $package . '/javascript/' . $name . '.js';

                break;

            case 'fp_module':
                $file = PATH_ADDONS . $name . '/javascript/' . $name . '.js';

                break;

            case 'template':
                // edit date from database should be sufficient;
                // in some cases the file might have more recent edits, but skipping the check here
                // to reduce the load
                $templateModel = ee('Model')->get('Template', $name)->with('TemplateGroup')->filter('template_type', 'js')->first(true);
                if (! empty($templateModel)) {
                    return $templateModel->edit_date;
                }

                break;

            default:
                return 0;
        }

        return file_exists($file) ? filemtime($file) : 0;
    }

    /**
     * Set the right navigation
     *
     * @param array
     * @param string
     * @return int
     */
    public function set_right_nav($nav = array())
    {
        ee()->view->cp_right_nav = array_reverse($nav);
    }

    /**
     * URL to the current page unless POST data exists - in which case it
     * goes to the root controller.  To use the result, prefix it with BASE.AMP
     *
     * @return string
     */
    public function get_safe_refresh()
    {
        static $url = '';

        if (! $url) {
            // We have 2 types of URLs:
            //   1. index.php?/cp/path/to/controller/with/arguments
            //   2. index.php?D=cp&C=cp&M=homepage
            //
            // In the case of #1 we likely built it with ee('CP/URL')->make() thus
            // we will store the needed parts to rebuild it.
            //
            // In the case of #2 we will build out a string to return

            $uri = ee()->uri->uri_string();
            if ($uri) {
                $args = array();
                foreach ($_GET as $key => $val) {
                    if ($key == 'S' or $key == 'D' or $key == 'C' or $key == 'M') {
                        continue;
                    }

                    // If a GET argument was POSTed, use that instead
                    $args[$key] = (isset($_POST[$key])) ? $_POST[$key] : $val;
                }

                $url = json_encode(array('path' => $uri, 'arguments' => $args));
            } else {
                $go_to_c = (count($_POST) > 0);
                $page = '';

                foreach ($_GET as $key => $val) {
                    if ($key == 'S' or $key == 'D' or ($go_to_c && $key != 'C')) {
                        continue;
                    }

                    $page .= $key . '=' . $val . AMP;
                }

                if (strlen($page) > 4 && substr($page, -5) == AMP) {
                    $page = substr($page, 0, -5);
                }

                $url = $page;
            }
        }

        return $url;
    }

    /**
     * Returns GET variables from the current CP URL. Useful for replicating
     * the state of a view if you need to go away from it, POST requests for
     * example.
     *
     * @return array GET array filtered of proprietary CP keys
     */
    public function get_url_state()
    {
        $filtered_get = array_filter(array_keys($_GET), function ($key) {
            return ! in_array($key, array('D', 'C', 'S', 'M'));
        });

        return array_intersect_key($_GET, array_flip($filtered_get));
    }

    /**
     * Get Quicklinks
     *
     * Does a lookup for quick links.  Based on the URL we determine if it is external or not
     *
     * @return array
     */
    private function _get_quicklinks($quick_links)
    {
        $len = strlen(ee()->config->item('cp_url'));

        $link = array();

        $count = 0;

        foreach ($quick_links as $ql) {
            $link[$count] = array(
                'link' => ee('CP/URL')->makeFromString($ql['link']),
                'title' => $ql['title'],
                'external' => true
            );

            $count++;
        }

        return $link;
    }

    /**
     * Abstracted Way to Add a Breadcrumb Links
     *
     * @return void
     */
    public function set_breadcrumb($link, $title)
    {
        static $_crumbs = array();

        if (is_object($link)) {
            $link = $link->compile();
        }

        $_crumbs[$link] = $title;
        ee()->view->cp_breadcrumbs = $_crumbs;
    }

    /**
     * Load Package JS
     *
     * Load a javascript file from a package
     *
     * @param  string
     * @return void
     */
    public function load_package_js($file)
    {
        $current_top_path = ee()->load->first_package_path();
        $package = trim(str_replace(array(PATH_THIRD, PATH_ADDONS, 'views'), '', $current_top_path), '/');

        $this->add_js_script(array('package' => $package . ':' . $file));
    }

    /**
     * Load Package CSS
     *
     * Load a stylesheet from a package
     *
     * @param  string
     * @return void
     */
    public function load_package_css($file)
    {
        $current_top_path = ee()->load->first_package_path();
        $package = trim(str_replace(array(PATH_THIRD, PATH_ADDONS, 'views'), '', $current_top_path), '/');

        if (REQ == 'CP') {
            $url = BASE . AMP . 'C=css' . AMP . 'M=third_party' . AMP . 'package=' . $package . AMP . 'file=' . $file;
        } else {
            $url = ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Channel', 'combo_loader') . AMP . 'type=css' . AMP . 'package=' . $package . AMP . 'file=' . $file;
        }

        $this->add_to_head('<link type="text/css" rel="stylesheet" href="' . $url . '" />');
    }

    /**
     * Add Header Data
     *
     * Add any string to the <head> tag
     *
     * @param  string
     * @return string
     */
    public function add_to_head($data)
    {
        // Deprecated for scripts. Let's encourage good practices. This will
        // also let us move jquery in the future.
        if (strpos($data, '<script') !== false) {
            ee()->load->library('logger');
            ee()->logger->deprecated('2.8', 'CP::add_to_foot() for scripts');
        }

        $this->its_all_in_your_head[] = $data;
    }

    /**
     * Returns the array of items to be added in the header
     *
     * @return array The array of items to be added in the header
     */
    public function get_head()
    {
        return $this->its_all_in_your_head;
    }

    /**
     * Add Footer Data
     *
     * Add any string above the </body> tag
     *
     * @param  string
     * @return string
     */
    public function add_to_foot($data)
    {
        $this->footer_item[] = $data;
    }

    /**
     * Returns the array of items to be added in the footer
     *
     * @return array The array of items to be added in the footer
     */
    public function get_foot()
    {
        return $this->footer_item;
    }

    /**
     * Allowed Group
     *
     * Member access validation
     *
     * @deprecated 5.0.0 Use ee('Permission')->hasAny() instead
     * @param  string  any number of permission names
     * @return bool    TRUE if member has any permissions in the set
     */
    public function allowed_group_any()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('5.0.0', "ee('Permission')->hasAny()");

        $which = func_get_args();

        return ee('Permission')->hasAny($which);
    }

    /**
     * Allowed Group
     *
     * Member access validation
     *
     * @deprecated 5.0.0 Use ee('Permission')->hasAll() instead
     * @param  string  any number of permission names
     * @return bool    TRUE if member has all permissions
     */
    public function allowed_group()
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('5.0.0', "ee('Permission')->hasAll()");

        $which = func_get_args();

        return ee('Permission')->hasAll($which);
    }

    /**
     * Is Module Installed?
     *
     * Returns array of installed modules.
     *
     * @return array
     */
    public function get_installed_modules()
    {
        if (! is_array($this->installed_modules)) {
            $this->installed_modules = array();

            ee()->db->select('LOWER(module_name) AS name');
            ee()->db->order_by('module_name');
            $query = ee()->db->get('modules');

            if ($query->num_rows()) {
                foreach ($query->result_array() as $row) {
                    $this->installed_modules[$row['name']] = $row['name'];
                }
            }
        }

        return $this->installed_modules;
    }

    /**
     * Invalid Custom Field Names
     *
     * Tracks "reserved" words to avoid variable name collision
     *
     * @return array
     */
    public function invalid_custom_field_names()
    {
        static $invalid_fields = array();

        if (! empty($invalid_fields)) {
            return $invalid_fields;
        }

        $channel_vars = array(
            'author', 'author_id', 'avatar_image_height',
            'avatar_image_width', 'avatar_url', 'comment_auto_path',
            'comment_entry_id_auto_path', 'comment_total', 'comment_url_title_path', 'count',
            'edit_date', 'email', 'entry_date', 'entry_id',
            'entry_id_path', 'expiration_date', 'forum_topic_id',
            'gmt_edit_date', 'gmt_entry_date',
            'ip_address', 'member_search_path', 'month',
            'permalink', 'photo_image_height',
            'photo_image_width', 'photo_url', 'profile_path',
            'recent_comment_date', 'relative_date', 'relative_url',
            'screen_name', 'signature', 'signature_image_height',
            'signature_image_url', 'signature_image_width', 'status',
            'switch', 'title', 'title_permalink', 'total_results',
            'trimmed_url', 'url_as_email_as_link', 'url_or_email',
            'url_or_email_as_author', 'url_title', 'url_title_path',
            'username', 'channel', 'channel_id', 'year', 'content'
        );

        $global_vars = array(
            'app_version', 'captcha', 'charset', 'current_time',
            'debug_mode', 'elapsed_time', 'email', 'embed', 'encode',
            'group_description', 'group_id', 'gzip_mode', 'hits',
            'homepage', 'ip_address', 'ip_hostname', 'lang',
            'member_group', 'member_id', 'member_profile_link', 'path',
            'private_messages', 'screen_name', 'site_index', 'site_name',
            'site_url', 'stylesheet', 'total_comments', 'total_entries',
            'total_forum_posts', 'total_forum_topics', 'total_queries',
            'username', 'webmaster_email', 'version'
        );

        $orderby_vars = array(
            'comment_total', 'date', 'edit_date', 'expiration_date',
            'most_recent_comment', 'random', 'screen_name', 'title',
            'url_title', 'username', 'view_count_four', 'view_count_one',
            'view_count_three', 'view_count_two'
        );

        $prefixes = array(
            'parents', 'siblings', 'file'
        );

        $control_structures = array(
            'if', 'else', 'elseif'
        );

        return $invalid_fields = array_unique(array_merge(
            $channel_vars,
            $global_vars,
            $orderby_vars,
            $prefixes,
            $control_structures
        ));
    }

    /**
     * Fetch Action IDs
     *
     *  @param string
     *  @param string
     *  @return mixed
     */
    public function fetch_action_id($class, $method)
    {
        ee()->db->select('action_id');
        ee()->db->where('class', $class);
        ee()->db->where('method', $method);
        $query = ee()->db->get('actions');

        if ($query->num_rows() == 0) {
            return false;
        }

        return $query->row('action_id');
    }

    /**
     * Site Switching Logic
     *
     * @param  int     $site_id    ID of site to switch to
     * @param  string  $redirect   Optional URL to redirect to after site switching is successful
     * @return void
     */
    public function switch_site($site_id, $redirect = '')
    {
        if (!ee('Permission')->isSuperAdmin() && !ee('Permission', $site_id)->can('access_cp')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (empty($redirect)) {
            $member = ee()->session->getMember();
            $redirect = $member->getCPHomepageURL($site_id);
        }

        // We set the cookie before switching prefs to ensure it uses current settings
        ee()->input->set_cookie('cp_last_site_id', $site_id, 0);

        ee()->config->site_prefs('', $site_id);

        ee()->functions->redirect($redirect);
    }

    public function makeChangelogLinkForVersion($version)
    {
        // Version in anchor is sans dots
        $version = implode('', explode('.', $version));
        $changelog_url = DOC_URL . 'installation/changelog.html#version-' . $version;

        return $changelog_url;
    }
}

// EOF
