<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Addons;

use CP_Controller;
use Michelf\MarkdownExtra;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Addon\Mcp;

/**
 * Addons Controller
 */
class Addons extends CP_Controller
{
    public $perpage = 25;
    public $params = array();
    public $base_url;

    public $assigned_modules = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee('CP/Alert')->makeDeprecationNotice()->now();

        if (! ee('Permission')->can('access_addons')) {
            // possible exception for FilePicker
            if (strncmp(ee()->uri->uri_string, 'cp/addons/settings/filepicker', 29) == 0) {
                if (! ee('Permission')->can('access_files')) {
                    show_error(lang('unauthorized_access'), 403);
                }
            } else {
                show_error(lang('unauthorized_access'), 403);
            }
        }

        ee()->lang->loadfile('addons');

        $this->params['perpage'] = $this->perpage; // Set a default

        $this->base_url = ee('CP/URL')->make('addons');

        ee()->load->library('addons');
        ee()->load->helper(array('file', 'directory'));
        ee()->legacy_api->instantiate('channel_fields');

        $member = ee()->session->getMember();

        $this->assigned_modules = $member->getAssignedModules()->pluck('module_id');

        // Make sure Filepicker is accessible for those who need it
        if (!ee('Permission')->isSuperAdmin() && ee('Permission')->can('access_files')) {
            $this->assigned_modules[] = ee('Model')->get('Module')->filter('module_name', 'Filepicker')->first()->getId();
        }
    }

    /**
     * Sets up the display filters
     *
     * @param int   $total  The total number of add-ons (used in the show filter)
     * @return  void
     */
    private function filters($total, $developers)
    {
        // First Party Add-on Filters

        // Status
        $status = ee('CP/Filter')->make('filter_by_first_status', 'filter_by_status', array(
            'installed' => strtolower(lang('installed')),
            'uninstalled' => strtolower(lang('uninstalled')),
            'updates' => strtolower(lang('needs_updates'))
        ));
        $status->disableCustomValue();

        $first_filters = ee('CP/Filter')
            ->add($status)
            ->add('Keyword')->withName('filter_by_first_keyword');

        // Third Party Add-on Filters

        // Status
        $status = ee('CP/Filter')->make('filter_by_third_status', 'filter_by_status', array(
            'installed' => strtolower(lang('installed')),
            'uninstalled' => strtolower(lang('uninstalled')),
            'updates' => strtolower(lang('needs_updates'))
        ));
        $status->disableCustomValue();

        // Developer
        $developer_options = array();
        foreach ($developers as $developer) {
            $developer_options[$this->makeDeveloperKey($developer)] = $developer;
        }
        $developer = ee('CP/Filter')->make('filter_by_developer', 'developer', $developer_options);
        $developer->disableCustomValue();

        $third_filters = ee('CP/Filter')
            ->add($status)
            ->add($developer)
            ->add('Keyword')->withName('filter_by_third_keyword');

        // When filtering the first party table keep the third party filter values
        $filter_base_url['first'] = clone $this->base_url;
        $filter_base_url['first']->addQueryStringVariables($third_filters->values());

        // Retain the third party page
        if (ee()->input->get('third_page')) {
            $filter_base_url['first']->setQueryStringVariable('third_page', ee()->input->get('third_page'));
        }

        // When filtering the third party table keep the first party filter values
        $filter_base_url['third'] = clone $this->base_url;
        $filter_base_url['third']->addQueryStringVariables($first_filters->values());

        // Retain the third party page
        if (ee()->input->get('first_page')) {
            $filter_base_url['third']->setQueryStringVariable('first_page', ee()->input->get('first_page'));
        }

        ee()->view->filters = array(
            'first' => $first_filters->render($filter_base_url['first']),
            'third' => $third_filters->render($filter_base_url['third'])
        );
        $this->params = array_merge($first_filters->values(), $third_filters->values());
        $this->base_url->addQueryStringVariables($this->params);
    }

    private function makeDeveloperKey($str)
    {
        return strtolower(str_replace(' ', '_', $str));
    }

    /**
     * Index function
     *
     * @return  void
     */
    public function index()
    {
        if (ee()->input->post('bulk_action') == 'install') {
            $this->install(ee()->input->post('selection'));
        } elseif (ee()->input->post('bulk_action') == 'remove') {
            $this->remove(ee()->input->post('selection'));
        } elseif (ee()->input->post('bulk_action') == 'update') {
            $this->update(ee()->input->post('selection'));
        }

        ee()->view->cp_page_title = lang('addon_manager');
        ee()->view->cp_heading = array(
            'first' => lang('addons'),
            'third' => lang('third_party_addons')
        );

        if (ee()->config->item('allow_extensions') == 'n') {
            ee('CP/Alert')->makeInline('extensions')
                ->asWarning()
                ->withTitle(lang('extensions_disabled'))
                ->addToBody(lang('extensions_disabled_message'))
                ->now();
        }

        $vars = array(
            'tables' => array(
                'first' => null,
                'third' => null
            )
        );

        $addons = $this->getAllAddons();

        // Filter list for non-super admins
        if (! ee('Permission')->isSuperAdmin()) {
            $that = $this;
            $addons = array_filter($addons, function ($addon) use ($that) {
                return (isset($addon['module_id']) && in_array($addon['module_id'], $that->assigned_modules));
            });
        }

        $return_url = ee('CP/URL')->getCurrentUrl();
        $vars['form_url'] = $this->base_url->setQueryStringVariable('return', $return_url->encode());

        // Create the urls for managing the add-on
        foreach ($addons as $key => $addon) {
            $addons[$key]['install_url'] = ee('CP/URL')->make('addons/install/' . $addon['package'], ['return' => $return_url->encode()]);
            $addons[$key]['update_url'] = ee('CP/URL')->make('addons/update/' . $addon['package'], ['return' => $return_url->encode()]);
            $addons[$key]['remove_url'] = ee('CP/URL')->make('addons/remove/' . $addon['package'], ['return' => $return_url->encode()]);
            $addons[$key]['confirm_url'] = ee('CP/URL')->make('addons/confirm/' . $addon['package']);
        }

        // Sort the add-ons alphabetically
        ksort($addons);
        // Ensure Pro is listed first
        if (isset($addons['pro'])) {
            $pro = $addons['pro'];
            unset($addons['pro']);
            $addons = array_reverse($addons, true);
            $addons['pro'] = $pro;
            $addons = array_reverse($addons, true);
        }

        $vars['uninstalled'] = array_filter($addons, function ($addon) {
            return ! $addon['installed'];
        });

        $vars['installed'] = array_filter($addons, function ($addon) {
            return $addon['installed'];
        });

        // Uninstalled add-ons
        $vars['updates'] = array_filter($addons, function ($addon) {
            return isset($addon['update']);
        });

        $vars['header'] = array(
            'search_button_value' => lang('search_addons_button'),
            'title' => ee()->view->cp_page_title,
            'form_url' => $vars['form_url']
        );

        ee()->javascript->set_global('lang.remove_confirm', lang('addon') . ': <b>### ' . lang('addons') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => ['cp/confirm_remove', 'cp/add-ons'],
        ));

        ee()->view->cp_breadcrumbs = array(
            '' => lang('addons')
        );

        ee()->cp->render('addons/index', $vars);
    }

    /**
     * Extra dialog for removal confirmation
    */
    public function confirm()
    {
        $vars = array();
        $selected = ee()->uri->segment('4');
        $desc = '';
        $fields = [];

        $channelFieldQuery = ee('Model')->get('ChannelField')
            ->filter('field_type', $selected);
        if ($channelFieldQuery->count() > 0) {
            $title = lang('fieldtype_is_in_use');
            $fields = array_merge($fields, $channelFieldQuery->all()->getDictionary('field_id', 'field_label'));
        }

        $gridFieldQuery = ee('db')->select('channel_fields.field_id, channel_fields.field_label')
            ->from('channel_fields')
            ->join('grid_columns', 'channel_fields.field_id = grid_columns.field_id', 'left')
            ->where('col_type', $selected)
            ->get();
        if ($gridFieldQuery->num_rows() > 0) {
            $title = lang('fieldtype_is_in_use');
            foreach ($gridFieldQuery->result_array() as $row) {
                $fields[$row['field_id']] = $row['field_label'];
            }
        }

        if (!empty($fields)) {
            $desc = implode(', ', $fields) . BR;
        }

        $desc .= lang('move_toggle_to_confirm');

        if (isset($title)) {
            $vars['fieldset'] = [
                'group' => 'delete-confirm',
                'setting' => [
                    'title' => $title,
                    'desc' => $desc,
                    'fields' => [
                        'confirm' => [
                            'type' => 'toggle',
                            'value' => 0,
                        ]
                    ]
                ]
            ];
        }

        ee()->cp->render('files/delete_confirm', $vars);
    }

    /**
     * Compiles a list of all available add-ons
     *
     * @return array An associative array of add-on data
     */
    private function getAllAddons()
    {
        $addon_infos = ee('Addon')->all();

        $addons = [];

        foreach ($addon_infos as $name => $info) {
            $info = ee('Addon')->get($name);

            if ($info->get('built_in')) {
                continue;
            }

            $addon = $this->getExtension($name);
            //$addon = array_merge($addon, $this->getJumpMenu($name));
            $addon = array_merge($addon, $this->getFieldType($name));
            $addon = array_merge($addon, $this->getPlugin($name));
            $addon = array_merge($addon, $this->getModule($name));

            if (! empty($addon)) {
                if (file_exists($info->getPath() . '/README.md')) {
                    $addon['manual_url'] = ee('CP/URL')->make('addons/manual/' . $name);
                    $addon['manual_external'] = false;
                } elseif ($info->get('docs_url')) {
                    $addon['manual_url'] = ee()->cp->masked_url($info->get('docs_url'));
                    $addon['manual_external'] = true;
                }

                $addon['icon_url'] = $info->getIconUrl();
                $addon['license_status'] = $info->checkCachedLicenseResponse();

                $addons[$name] = $addon;
            }
        }

        return $addons;
    }

    /**
     * Updates an add-on
     *
     * @param string $addon The name of the add-on to update
     * @return void
     */
    public function update($addons)
    {
        if (
            ! ee('Permission')->can('admin_addons') or
            ee('Request')->method() !== 'POST'
        ) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($addons)) {
            $addons = array($addons);
        }

        $updated = array(
            'first' => array(),
            'third' => array()
        );

        foreach ($addons as $addon) {
            $addon_info = ee('pro:Addon')->get($addon);
            $party = ($addon_info->getAuthor() == 'ExpressionEngine') ? 'first' : 'third';

            $addon_info->updateConsentRequests();

            $module = $this->getModule($addon);
            if (
                ! empty($module)
                && $module['installed'] === true
                && array_key_exists('update', $module)
            ) {
                $installed = ee()->addons->get_installed('modules', true);

                $class = $addon_info->getInstallerClass();
                $version = $installed[$addon]['module_version'];

                ee()->load->add_package_path($installed[$addon]['path']);

                $UPD = new $class();

                $name = $module['name'];

                if ($UPD->update($version) !== false) {
                    $new_version = $addon_info->getVersion();
                    if (version_compare($version, $new_version, '<')) {
                        $module = ee('Model')->get('Module', $installed[$addon]['module_id'])
                            ->first();
                        $module->module_version = $new_version;
                        $module->save();

                        $updated[$party][$addon] = $name;
                    }
                }
            }

            $fieldtype = $this->getFieldtype($addon);
            if (
                ! empty($fieldtype)
                && $fieldtype['installed'] === true
                && array_key_exists('update', $fieldtype)
            ) {
                ee()->api_channel_fields->include_handler($addon);
                $FT = ee()->api_channel_fields->setup_handler($addon, true);
                $update_ft = false;
                if (!method_exists($FT, 'update')) {
                    $update_ft = true;
                } else {
                    if ($FT->update($fieldtype['version']) !== false) {
                        if (ee()->api_channel_fields->apply('update', array($fieldtype['version'])) !== false) {
                            $update_ft = true;
                        }
                    }
                }
                if ($update_ft) {
                    $model = ee('Model')->get('Fieldtype')
                        ->filter('name', $addon)
                        ->first();

                    $model->version = $addon_info->getVersion();
                    $model->save();

                    if (! isset($updated[$party][$addon])) {
                        $updated[$party][$addon] = $fieldtype['name'];
                    }
                }
            }

            $extension = $this->getExtension($addon);
            if (
                ! empty($extension)
                && $extension['installed'] === true
                && array_key_exists('update', $extension)
            ) {
                $class = $addon_info->getExtensionClass();

                $class_name = $extension['class'];
                $Extension = new $class();
                $Extension->update_extension($extension['version']);
                ee()->extensions->version_numbers[$class_name] = $addon_info->getVersion();

                $model = ee('Model')->get('Extension')
                    ->filter('class', $class_name)
                    ->all();

                $model->version = $addon_info->getVersion();
                $model->save();

                if (! isset($updated[$party][$addon])) {
                    $updated[$party][$addon] = $extension['name'];
                }
            }

            $plugin = $this->getPlugin($addon);
            if (
                ! empty($plugin)
                && $plugin['installed'] === true
                && array_key_exists('update', $plugin)
            ) {
                $typography = 'n';

                if ($addon_info->get('plugin.typography')) {
                    $typography = 'y';
                }

                $model = ee('Model')->get('Plugin')
                    ->filter('plugin_package', $plugin['package'])
                    ->first();

                $model->plugin_name = $plugin['name'];
                $model->plugin_package = $plugin['package'];
                $model->plugin_version = $addon_info->getVersion();
                $model->is_typography_related = $typography;
                $model->save();

                if (! isset($updated[$party][$addon])) {
                    $updated[$party][$addon] = $plugin['name'];
                }
            }

            $addon_info->updateDashboardWidgets();
            $addon_info->updateProlets();
        }

        ee()->cache->file->delete('/addons-status');
        ee('CP/JumpMenu')->clearAllCaches();

        foreach (array('first', 'third') as $party) {
            if (! empty($updated[$party])) {
                $alert = ee('CP/Alert')->makeInline($party . '-party')
                    ->asSuccess()
                    ->withTitle(lang('addons_updated'))
                    ->addToBody(lang('addons_updated_desc'))
                    ->addToBody(array_values($updated[$party]))
                    ->defer();
            }
        }

        $return = $this->base_url;

        if (ee()->input->get('return')) {
            $return = ee('CP/URL')->decodeUrl(ee()->input->get('return'));
        }

        ee()->functions->redirect($return);
    }

    /**
     * Installs an add-on
     *
     * @param   str|array   $addons The name(s) of add-ons to install
     * @return  void
     */
    public function install($addons)
    {
        if (
            ! ee('Permission')->can('admin_addons') or
            ee('Request')->method() !== 'POST'
        ) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($addons)) {
            $addons = array($addons);
        }

        ee()->load->library('addons/addons_installer');

        $installed = array(
            'first' => array(),
            'third' => array()
        );

        // Preflight for consents
        $requests = [
            'first' => [],
            'third' => []
        ];

        $can_install = true;

        foreach ($addons as $addon) {
            $info = ee('Addon')->get($addon);

            $requires = $info->getProvider()->get('requires');
            if (!empty($requires) && isset($requires['php'])) {
                if (version_compare(PHP_VERSION, $requires['php'], '<')) {
                    $can_install = false;
                    ee('CP/Alert')->makeInline($addon . 'NotInstalled_php')
                        ->asWarning()
                        ->withTitle(lang('addons_not_installed'))
                        ->addToBody(sprintf(lang('version_required'), 'PHP', $requires['php']))
                        ->addToBody([$info->getName()])
                        ->defer();
                }
            }

            if (!empty($requires) && isset($requires['ee'])) {
                if (version_compare(APP_VER, $requires['ee'], '<')) {
                    $can_install = false;
                    ee('CP/Alert')->makeInline($addon . 'NotInstalled_ee')
                        ->asWarning()
                        ->withTitle(lang('addons_not_installed'))
                        ->addToBody(sprintf(lang('version_required'), 'ExpressionEngine', $requires['ee']))
                        ->addToBody([$info->getName()])
                        ->defer();
                }
            }

            $party = ($info->getAuthor() == 'ExpressionEngine') ? 'first' : 'third';
            $requests[$party] = array_merge($requests[$party], $info->getInstalledConsentRequests());
        }

        foreach (array('first', 'third') as $party) {
            if (! empty($requests[$party])) {
                $can_install = false;
                $alert = ee('CP/Alert')->makeInline($party . '-party')
                    ->asIssue()
                    ->withTitle(lang('addons_not_installed'))
                    ->addToBody(lang('existing_consent_request'))
                    ->addToBody($requests[$party])
                    ->addToBody(lang('contact_developer'))
                    ->defer();
            }
        }

        if ($can_install) {
            foreach ($addons as $addon) {
                $info = ee('pro:Addon')->get($addon);
                ee()->load->add_package_path($info->getPath());

                $party = ($info->getAuthor() == 'ExpressionEngine') ? 'first' : 'third';

                try {
                    $info->installConsentRequests();
                } catch (\Exception $e) {
                    $alert = ee('CP/Alert')->makeInline($party . '-party')
                        ->asIssue()
                        ->withTitle(lang('addons_not_installed'))
                        ->addToBody(lang('existing_consent_request'))
                        ->addToBody([$addon])
                        ->addToBody(lang('contact_developer'))
                        ->defer();

                    break;
                }

                $module = $this->getModule($addon);
                if (! empty($module) && $module['installed'] === false) {
                    $name = $this->installModule($addon);
                    if ($name) {
                        $installed[$party][$addon] = $name;
                    }
                }

                $fieldtype = $this->getFieldtype($addon);
                if (! empty($fieldtype) && $fieldtype['installed'] === false) {
                    $name = $this->installFieldtype($addon);
                    if ($name && ! isset($installed[$addon])) {
                        $installed[$party][$addon] = $name;
                    }
                }

                $extension = $this->getExtension($addon);
                if (! empty($extension) && $extension['installed'] === false) {
                    $name = $this->installExtension($addon);
                    if ($name && ! isset($installed[$addon])) {
                        $installed[$party][$addon] = $name;
                    }
                }

                $plugin = $this->getPlugin($addon);
                if (! empty($plugin) && $plugin['installed'] === false) {
                    $typography = 'n';
                    if ($info->get('plugin.typography')) {
                        $typography = 'y';
                    }

                    $model = ee('Model')->make('Plugin');
                    $model->plugin_name = $plugin['name'];
                    $model->plugin_package = $plugin['package'];
                    $model->plugin_version = $info->getVersion();
                    $model->is_typography_related = $typography;
                    $model->save();

                    if (! isset($installed[$addon])) {
                        $installed[$party][$addon] = $plugin['name'];
                    }
                }

                $info->updateDashboardWidgets();
                $info->updateProlets();

                ee()->load->remove_package_path($info->getPath());
            }

            foreach (array('first', 'third') as $party) {
                if (! empty($installed[$party])) {
                    $alert = ee('CP/Alert')->makeInline($party . '-party')
                        ->asSuccess()
                        ->withTitle(lang('addons_installed'))
                        ->addToBody(lang('addons_installed_desc'))
                        ->addToBody(array_values($installed[$party]))
                        ->defer();
                }
            }
        }

        ee()->cache->file->delete('/addons-status');
        ee('CP/JumpMenu')->clearAllCaches();

        $return = $this->base_url;

        if (ee()->input->get('return')) {
            $return = ee('CP/URL')->decodeUrl(ee()->input->get('return'));
        }

        ee()->functions->redirect($return);
    }

    /**
     * Uninstalls an add-on
     *
     * @param   str|array   $addons The name(s) of add-ons to uninstall
     * @return  void
     */
    public function remove($addons)
    {
        if (! ee('Permission')->can('admin_addons')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($addons)) {
            $addons = array($addons);
        }

        ee()->load->library('addons/addons_installer');

        $uninstalled = array(
            'first' => array(),
            'third' => array()
        );

        foreach ($addons as $addon) {
            $info = ee('pro:Addon')->get($addon);

            $info->removeConsentRequests();

            if (empty($info)) {
                continue;
            }

            $party = ($info->getAuthor() == 'ExpressionEngine') ? 'first' : 'third';

            $module = $this->getModule($addon);
            if (! empty($module) && $module['installed'] === true) {
                $name = $this->uninstallModule($addon);
                if ($name) {
                    $uninstalled[$party][$addon] = $name;
                }
            }

            $fieldtype = $this->getFieldtype($addon);
            // no need to check if installed, we'll let the uninstaller handle

            if (! empty($fieldtype)) {
                $name = $this->uninstallFieldtype($addon);
                if ($name && ! isset($uninstalled[$party][$addon])) {
                    $uninstalled[$party][$addon] = $name;
                }
            }

            $extension = $this->getExtension($addon);
            if (! empty($extension) && $extension['installed'] === true) {
                $name = $this->uninstallExtension($addon);
                if ($name && ! isset($uninstalled[$party][$addon])) {
                    $uninstalled[$party][$addon] = $name;
                }
            }

            $plugin = $this->getPlugin($addon);
            if (! empty($plugin) && $plugin['installed'] === true) {
                ee('Model')->get('Plugin')
                    ->filter('plugin_package', $addon)
                    ->delete();

                if (! isset($uninstalled[$party][$addon])) {
                    $uninstalled[$party][$addon] = $plugin['name'];
                }
            }

            if ($addon != 'pro') {
                $info->updateDashboardWidgets(true);
                $info->updateProlets(true);
            }
        }

        ee('CP/JumpMenu')->clearAllCaches();

        foreach (array('first', 'third') as $party) {
            if (! empty($uninstalled[$party])) {
                $alert = ee('CP/Alert')->makeInline($party . '-party')
                    ->asSuccess()
                    ->withTitle(lang('addons_uninstalled'))
                    ->addToBody(lang('addons_uninstalled_desc'))
                    ->addToBody(array_values($uninstalled[$party]))
                    ->defer();
            }
        }

        $return = $this->base_url;

        if (ee()->input->get('return')) {
            $return = ee('CP/URL')->decodeUrl(ee()->input->get('return'));
        }

        ee()->functions->redirect($return);
    }

    /**
     * Display add-on settings
     *
     * @param   string $addon  The name of add-on whose settings to display
     * @return  void
     */
    public function settings($addon, $method = null)
    {
        $this->assertUserHasAccess($addon);
        $info = ee('Addon')->get($addon);

        if (empty($info)) {
            show_404();
        }

        ee()->view->cp_page_title = lang('addon_manager');

        $vars = array();
        $breadcrumb = array(
            ee('CP/URL')->make('addons')->compile() => lang('addons')
        );

        if (is_null($method)) {
            $method = (ee()->input->get_post('method') !== false) ? ee()->input->get_post('method') : 'index';
        }

        $licenseResponse = $info->checkCachedLicenseResponse();
        $licenseStatusBadge = '';
        switch ($licenseResponse) {
            case 'trial':
                $licenseStatusBadge = '<a class="license-status-badge license-status-trial" href="https://expressionengine.com/store/licenses" target="_blank">' . lang('license_trial') . '</a>';

                break;
            case 'update_available':
                $licenseStatusBadge = '<a class="license-status-badge license-status-update_available" href="https://expressionengine.com/store/licenses#update-available" target="_blank">' . lang('license_update_available') . '</a>';

                break;
            case 'invalid':
                $licenseStatusBadge = '<a class="license-status-badge license-status-invalid" href="https://expressionengine.com/store/licenses" target="_blank">' . lang('license_invalid') . '</a>';
                // Pro got it's own message
                if ($addon !== 'pro') {
                    ee('CP/Alert')->makeBanner('license-error')
                        ->asIssue()
                        ->canClose()
                        ->withTitle(lang('unlicensed_addon'))
                        ->addToBody(sprintf(lang('unlicensed_addon_message'), $info->getName()))
                        ->now();
                }

                break;
            case 'expired':
                $licenseStatusBadge = '<a class="license-status-badge license-status-expired" href="https://expressionengine.com/store/licenses" target="_blank">' . lang('license_license_expired') . '</a>';

                break;
            default:
                break;
        }

        $requires = $info->getProvider()->get('requires');
        if (!empty($requires) && isset($requires['php'])) {
            if (version_compare(PHP_VERSION, $requires['php'], '<')) {
                ee('CP/Alert')->makeBanner($addon . 'NotFunctional_php')
                    ->asWarning()
                    ->withTitle(sprintf(lang('addon_not_fully_functional'), $info->getName()))
                    ->addToBody(sprintf(lang('version_required'), 'PHP', $requires['php']))
                    ->now();
            }
        }

        if (!empty($requires) && isset($requires['ee'])) {
            if (version_compare(APP_VER, $requires['ee'], '<')) {
                ee('CP/Alert')->makeBanner($addon . 'NotFunctional_ee')
                    ->asWarning()
                    ->withTitle(sprintf(lang('addon_not_fully_functional'), $info->getName()))
                    ->addToBody(sprintf(lang('version_required'), 'ExpressionEngine', $requires['ee']))
                    ->now();
            }
        }

        // Module
        $module = $this->getModule($addon);
        if (! empty($module) && $module['installed'] === true) {
            $data = $this->getModuleSettings($addon, $method, array_slice(func_get_args(), 2));

            $addon_header = (isset(ee()->cp->header)) ? ee()->cp->header : (isset(ee()->view->header) ? ee()->view->header : []);
            $header = array_merge($addon_header, array('title' => $module['name'] . ' ' . $licenseStatusBadge));

            ee()->view->header = $header;
            ee()->view->cp_heading = $module['name'] . ' ' . lang('configuration');

            if (is_array($data)) {
                if (isset($data['ajax']) && $data['ajax']) {
                    return $data['body'];
                }

                $vars['_module_cp_body'] = $data['body'];

                if (isset($data['heading'])) {
                    ee()->view->cp_heading = $data['heading'];
                }

                $self_link = ee('CP/URL')->make('addons/settings/' . $addon)->compile();
                if (isset($data['breadcrumb'])) {
                    if (!isset($data['breadcrumb'][$self_link])) {
                        $breadcrumb[$self_link] = $module['name'];
                    }
                    $breadcrumb = array_merge($breadcrumb, $data['breadcrumb']);
                } else {
                    $breadcrumb[$self_link] = $module['name'];
                }
            } else {
                $vars['_module_cp_body'] = $data;
                $breadcrumb[ee('CP/URL')->make('addons/settings/' . $addon)->compile()] = $module['name'];
            }
        } else {
            // Fieldtype
            $fieldtype = $this->getFieldtype($addon);
            if (! empty($fieldtype) && $fieldtype['installed'] === true) {
                if ($method == 'save') {
                    $this->saveFieldtypeSettings($fieldtype);
                    ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $addon));
                }

                $vars['_module_cp_body'] = $this->getFieldtypeSettings($fieldtype);
                $breadcrumb[ee('CP/URL')->make('addons/settings/' . $addon)->compile()] = $fieldtype['name'];
                ee()->view->cp_heading = $fieldtype['name'] . ' ' . lang('configuration');
            } else {
                // Extension
                $extension = $this->getExtension($addon);
                if (! empty($extension) && $extension['installed'] === true) {
                    if ($method == 'save') {
                        $this->saveExtensionSettings($addon);
                        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $addon));
                    }

                    $vars['_module_cp_body'] = $this->getExtensionSettings($addon);
                    $breadcrumb[ee('CP/URL')->make('addons/settings/' . $addon)->compile()] = $extension['name'];
                    ee()->view->cp_heading = $extension['name'] . ' ' . lang('configuration') . ' ' . $licenseStatusBadge;
                }
            }
        }

        if (! isset($vars['_module_cp_body'])) {
            show_error(lang('requested_module_not_installed') . NBS . $addon);
        }

        ee()->view->cp_breadcrumbs = $breadcrumb;
        ee()->view->cp_page_title = ee()->view->cp_heading;
        ee()->view->body_class = 'add-on-layout';

        ee()->cp->render('addons/settings', $vars);
    }

    /**
     * Display plugin manual/documentation
     *
     * @param   string $addon  The name of plugin whose manual to display
     * @return  void
     */
    public function manual($addon = null)
    {
        if (! $addon) {
            show_404();
        }

        $this->assertUserHasAccess($addon);

        try {
            $info = ee('Addon')->get($addon);
        } catch (\Exception $e) {
            show_error(lang('requested_module_not_installed') . NBS . $addon);
        }

        $readme_file = $info->getPath() . '/README.md';

        if (! file_exists($readme_file)) {
            show_404();
        }

        ee()->view->cp_page_title = $info->getName() . ' ' . lang('manual');

        $vars = array(
            'name' => $info->getName(),
            'version' => $this->formatVersionNumber($info->getVersion()),
            'author' => $info->getAuthor(),
            'author_url' => $info->get('author_url') ? ee()->cp->masked_url($info->get('author_url')) : '#',
            'docs_url' => $info->get('docs_url') ? ee()->cp->masked_url($info->get('docs_url')) : '#',
            'description' => $info->get('description')
        );

        // Some pre-processing:
        //   1. Remove any #'s at the start of the doc, since that will be redundant with the add-on info

        $readme = preg_replace('/^\s*#.*?\n/s', '', file_get_contents($readme_file));

        $parser = new MarkdownExtra();
        $parser->url_filter_func = function ($url) {
            return ee()->cp->masked_url($url);
        };
        $readme = $parser->transform($readme);

        // Some post-processing
        //   1. Step headers back (h2 becomes h1, h3 becomes, h2, etc.)
        //   2. Change codeblocks to textareas
        //   3. Add <mark> around h4's (params and variables)
        //   4. Pull out header tree for sidebar nav (h1 and h2 only)

        /*
        for ($i = 2, $j = 1; $i <=6; $i++, $j++)
        {
            $readme = str_replace(array("<h{$i}>", "</h{$i}>"), array("<h{$j}>", "</h{$j}>"), $readme);
        }
        */

        $pre_tags = array('<pre><code>', '</code></pre>', '<h4>', '</h4>');
        $post_tags = array('<textarea>', '</textarea>', '<h4><mark>', '</mark></h4>');

        $readme = str_replace($pre_tags, $post_tags, $readme);

        // [
        //  [0] => <h1>full tag</h1>
        //  [1] => 1
        //  [2] => full tag
        // ]
        preg_match_all('|<h([23])>(.*?)</h\\1>|', $readme, $matches, PREG_SET_ORDER);

        $nav = array();
        $child = array();
        foreach ($matches as $key => $match) {
            // give 'em id's so they are linkable
            $new_header = "<h{$match[1]} id=\"ref{$key}\">{$match[2]}</h{$match[1]}>";

            // just in case they use the same name in multiple headers, we need to id separately
            // hence preg_replace() with a limit instead of str_replace()
            $readme = preg_replace('/' . preg_quote($match[0], '/') . '/', $new_header, $readme, 1);

            if ($match[1] == 2) {
                // append any children (h3's) if they exist
                if (! empty($child)) {
                    $nav[] = $child;
                    $child = array();
                }

                $nav[strip_tags($match[2])] = "#ref{$key}";
            } else {
                // save the children for later. SAVE THE CHILDREN!
                $child[strip_tags($match[2])] = "#ref{$key}";
            }
        }

        // don't forget the youngest!
        if (! empty($child)) {
            $nav[] = $child;
        }

        // Register our menu and header
        ee()->view->left_nav = ee()->load->view(
            '_shared/left_nav',
            array('nav' => $nav),
            true
        );
        ee()->view->header = array(
            'title' => lang('addon_manager'),
            'form_url' => ee('CP/URL')->make('addons'),
            'search_button_value' => lang('search_addons_button')
        );

        $vars['readme'] = $readme;

        ee()->view->cp_heading = $vars['name'] . ' ' . lang('manual');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('addons')->compile() => lang('addons'),
            ee('CP/URL')->make('addons/settings/' . $addon)->compile() => $info->getName(),
            '' => lang('manual')
        );

        ee()->view->body_class = 'add-on-layout';

        ee()->cp->render('addons/manual', $vars);
    }

    /**
     * Get data on a module
     *
     * @param   string $name   The add-on name
     * @return  array       Add-on data in the following format:
     *   e.g. 'developer'    => 'native',
     *        'version'      => '--',
     *        'update'       => '2.0.4' (optional)
     *        'installed'    => FALSE,
     *        'name'         => 'FooBar',
     *        'package'      => 'foobar',
     *        'type'         => 'module',
     *        'settings_url' => '' (optional)
     */
    private function getModule($name)
    {
        try {
            $info = ee('Addon')->get($name);
        } catch (\Exception $e) {
            show_404();
        }

        if (empty($info)) {
            show_404();
        }

        if (! $info->hasModule()) {
            return array();
        }

        // Use lang file if present, otherwise fallback to addon.setup
        ee()->lang->loadfile($name, '', false);
        $display_name = (lang(strtolower($name) . '_module_name') != strtolower($name) . '_module_name')
            ? lang(strtolower($name) . '_module_name') : $info->getName();

        $data = array(
            'developer' => $info->getAuthor(),
            'version' => '--',
            'installed' => false,
            'name' => $display_name,
            'description' => $info->get('description'),
            'package' => $name,
            'type' => 'module',
        );

        $module = ee('Model')->get('Module')
            ->filter('module_name', $name)
            ->first();

        if ($module) {
            $data['module_id'] = $module->module_id;
            $data['installed'] = true;
            $data['version'] = $module->module_version;

            if ($info->get('settings_exist')) {
                $data['settings_url'] = ee('CP/URL')->make('addons/settings/' . $name);
            }

            if ($info->hasInstaller()) {
                $class = $info->getInstallerClass();

                ee()->load->add_package_path($info->getPath());

                $UPD = new $class();

                if (
                    version_compare($info->getVersion(), $module->module_version, '>')
                    && method_exists($UPD, 'update')
                ) {
                    $data['update'] = $info->getVersion();
                }
            }
        }

        return $data;
    }

    /**
     * Get data on a plugin
     *
     * @param   string $name   The add-on name
     * @return  array       Add-on data in the following format:
     *   e.g. 'developer'    => 'native',
     *        'version'      => '--',
     *        'installed'    => FALSE,
     *        'name'         => 'FooBar',
     *        'package'      => 'foobar',
     *        'type'         => 'plugin',
     *        'manual_url' => ''
     */
    private function getPlugin($name)
    {
        try {
            $info = ee('Addon')->get($name);
        } catch (\Exception $e) {
            show_404();
        }

        if (empty($info)) {
            show_404();
        }

        if (! $info->hasPlugin()) {
            return array();
        }

        $data = array(
            'developer' => $info->getAuthor(),
            'version' => '--',
            'installed' => false,
            'name' => $info->getName(),
            'description' => $info->get('description'),
            'package' => $name,
            'type' => 'plugin',
        );

        $model = ee('Model')->get('Plugin')
            ->filter('plugin_package', $name)
            ->first();

        if (! is_null($model)) {
            $data['installed'] = true;
            $data['version'] = $model->plugin_version;
            if (version_compare($info->getVersion(), $model->plugin_version, '>')) {
                $data['update'] = $info->getVersion();
            }
        }

        return $data;
    }

    /**
     * Get data on a fieldtype
     *
     * @param   string $name   The add-on name
     * @return  array       Add-on data in the following format:
     *   e.g. 'developer'    => 'native',
     *        'version'      => '--',
     *        'installed'    => FALSE,
     *        'name'         => 'FooBar',
     *        'package'      => 'foobar',
     *        'type'         => 'fieldtype',
     *        'settings'     => array(),
     *        'settings_url' => '' (optional)
     */
    private function getFieldtype($name)
    {
        try {
            $info = ee('Addon')->get($name);
        } catch (\Exception $e) {
            show_404();
        }

        if (empty($info)) {
            show_404();
        }

        if (! $info->hasFieldtype()) {
            return array();
        }

        $data = array(
            'developer' => $info->getAuthor(),
            'version' => '--',
            'installed' => false,
            'name' => $info->getName(),
            'description' => $info->get('description'),
            'package' => $name,
            'type' => 'fieldtype',
        );

        $model = ee('Model')->get('Fieldtype')
            ->filter('name', $name)
            ->first();

        if ($model) {
            $data['installed'] = true;
            $data['version'] = $model->version;

            if (version_compare($info->getVersion(), $model->version, '>')) {
                $data['update'] = $info->getVersion();
            }

            if ($info->get('settings_exist')) {
                if ($model->settings) {
                    $data['settings'] = $model->settings;
                }
                $data['settings_url'] = ee('CP/URL')->make('addons/settings/' . $name);
            }
        }

        return $data;
    }

    /**
     * Get data on a jump menu
     *
     * @param   string $name   The add-on name
     * @return  array       Jump data in the following format:
     *   e.g. 'icon'             => 'fa-plus',
     *        'command'          => 'create new entry',
     *        'command_title'    => 'Create <b>Entry</b> in <i>[channel]</i>',
     *        'dynamic'          => true,
     *        'addon'            => false,
     *        'target'           => 'publish/create'
     */
    private function getJumpMenu($name)
    {
        try {
            $info = ee('Addon')->get($name);
        } catch (\Exception $e) {
            show_404();
        }

        if (empty($info)) {
            show_404();
        }

        if (! $info->hasJumpMenu()) {
            return array();
        }

        $data['jumps'] = $info->getJumps();

        return $data;
    }

    /**
     * Get data on an extension
     *
     * @param   string $name   The add-on name
     * @return  array       Add-on data in the following format:
     *   e.g. 'developer'    => 'native',
     *        'version'      => '--',
     *        'update'       => '2.0.4' (optional)
     *        'installed'    => TRUE|FALSE,
     *        'name'         => 'FooBar',
     *        'package'      => 'foobar',
     *        'class'        => 'Foobar_ext',
     *        'enabled'      => NULL|TRUE|FALSE
     *        'settings_url' => '' (optional)
     */
    private function getExtension($name)
    {
        if (ee()->config->item('allow_extensions') != 'y') {
            return array();
        }

        try {
            $info = ee('Addon')->get($name);
        } catch (\Exception $e) {
            show_404();
        }

        if (empty($info)) {
            show_404();
        }

        if (! $info->hasExtension()) {
            return array();
        }

        $class_name = ucfirst($name) . '_ext';

        $data = array(
            'developer' => $info->getAuthor(),
            'version' => '--',
            'installed' => false,
            'enabled' => null,
            'name' => $info->getName(),
            'description' => $info->get('description'),
            'package' => $name,
            'class' => $class_name,
        );

        $extension = ee('Model')->get('Extension')
            ->filter('class', $class_name)
            ->first();

        if ($extension) {
            $data['version'] = $extension->version;
            $data['installed'] = true;
            $data['enabled'] = $extension->enabled;

            ee()->load->add_package_path($info->getPath());

            if (! class_exists($class_name)) {
                $file = $info->getPath() . '/ext.' . $name . '.php';
                if (
                    ee()->config->item('debug') == 2
                    or (ee()->config->item('debug') == 1 and ee('Permission')->isSuperAdmin())
                ) {
                    include($file);
                } else {
                    @include($file);
                }

                if (! class_exists($class_name)) {
                    trigger_error(str_replace(array('%c', '%f'), array(htmlentities($class_name), htmlentities($file)), lang('extension_class_does_not_exist')));

                    return array();
                }
            }

            // Get some details on the extension
            $ext_obj = new $class_name($extension->settings);
            if (
                version_compare($info->getVersion(), $extension->version, '>')
                && method_exists($ext_obj, 'update_extension') === true
            ) {
                $data['update'] = $info->getVersion();
            }

            if ($info->get('settings_exist')) {
                $data['settings_url'] = ee('CP/URL')->make('addons/settings/' . $name);
            }
        }

        return $data;
    }

    /**
     * Installs an extension
     *
     * @param  string  $addon  The add-on to install
     * @return string          The name of the add-on just installed
     */
    private function installExtension($addon)
    {
        $name = null;
        $module = ee()->security->sanitize_filename(strtolower($addon));
        $extension = $this->getExtension($addon);

        if (ee()->addons_installer->install($addon, 'extension', false)) {
            $name = $extension['name'];
        }

        return $name;
    }

    /**
     * Uninstalls a an extension
     *
     * @param  string  $addon  The add-on to uninstall
     * @return string          The name of the add-on just uninstalled
     */
    private function uninstallExtension($addon)
    {
        $name = null;
        $module = ee()->security->sanitize_filename(strtolower($addon));
        $extension = $this->getExtension($addon);

        if (ee()->addons_installer->uninstall($addon, 'extension', false)) {
            $name = $extension['name'];
        }

        return $name;
    }

    /**
     * Installs a module
     *
     * @param  string  $module The add-on to install
     * @return string          The name of the add-on just installed
     */
    private function installModule($module)
    {
        $name = null;
        $module = ee()->security->sanitize_filename(strtolower($module));
        ee()->lang->loadfile($module, '', false);

        if (ee()->addons_installer->install($module, 'module', false)) {
            try {
                $info = ee('Addon')->get($module);
            } catch (\Exception $e) {
                show_404();
            }

            $name = (lang(strtolower($module) . '_module_name') != strtolower($module) . '_module_name')
                ? lang(strtolower($module) . '_module_name') : $info->getName();
        }

        return $name;
    }

    /**
     * Uninstalls a module
     *
     * @param  string  $module The add-on to uninstall
     * @return string          The name of the add-on just uninstalled
     */
    private function uninstallModule($module)
    {
        $name = null;
        $module = ee()->security->sanitize_filename(strtolower($module));
        ee()->lang->loadfile($module, '', false);

        if (ee()->addons_installer->uninstall($module, 'module', false)) {
            try {
                $info = ee('Addon')->get($module);
            } catch (\Exception $e) {
                show_404();
            }

            $name = (lang(strtolower($module) . '_module_name') != strtolower($module) . '_module_name')
                ? lang(strtolower($module) . '_module_name') : $info->getName();
        }

        return $name;
    }

    /**
     * Installs a fieldtype
     *
     * @param  string  $fieldtype  The add-on to install
     * @return string              The name of the add-on just installed
     */
    private function installFieldtype($fieldtype)
    {
        $name = null;
        $fieldtype = ee()->security->sanitize_filename(strtolower($fieldtype));

        // Note, the addons_uninstaller will loop through all fieldtypes for the addon path and delete them
        if (ee()->addons_installer->install($fieldtype, 'fieldtype', false)) {
            $data = $this->getFieldtype($fieldtype);
            $name = $data['name'];
        }

        return $name;
    }

    /**
     * Uninstalls a fieldtype
     *
     * @param  string  $$fieldtype The add-on to uninstall
     * @return string              The name of the add-on just uninstalled
     */
    private function uninstallFieldtype($fieldtype)
    {
        $name = null;
        $fieldtype = ee()->security->sanitize_filename(strtolower($fieldtype));

        // Note, the addons_installer will loop through all fieldtypes for the addon path and delete them
        if (ee()->addons_installer->uninstall($fieldtype, 'fieldtype', false)) {
            $data = $this->getFieldtype($fieldtype);
            $name = $data['name'];
        }

        return $name;
    }

    /**
     * Render module-specific settings
     *
     * @param   string $name   The name of module whose settings to display
     * @return  string         The rendered settings (with HTML)
     */
    private function getModuleSettings($name, $method, $parameters)
    {
        if (empty($method)) {
            $method = 'index';
        }

        $addon = ee()->security->sanitize_filename(strtolower($name));

        $info = ee('Addon')->get($name);

        $module = ee('Model')->get('Module')
            ->filter('module_name', $name)
            ->first();

        if (! ee('Permission')->isSuperAdmin()) {
            // Do they have access to this module?
            if (! isset($module)) {
                show_error(lang('unauthorized_access'), 403);
            }

            $this->assertUserHasAccess($addon);
        } else {
            if (! isset($module)) {
                show_error(lang('requested_module_not_installed') . NBS . $addon);
            }
        }

        $view_folder = 'views';

        // set the view path
        define('MODULE_VIEWS', $info->getPath() . '/' . $view_folder . '/');

        // Add the helper/library load path and temporarily
        // switch the view path to the module's view folder
        ee()->load->add_package_path($info->getPath());

        // instantiate the module cp class
        $class = $info->getControlPanelClass();
        $mod = new $class();

        // add validation callback support to the mcp class (see EE_form_validation for more info)
        ee()->set('_mcp_reference', $mod);

        // make a copy of the original method name for method controller
        $originalMethod = $method;

        // its possible that a module will try to call a method that does not exist
        // either by accident (ie: a missed function) or by deliberate user url hacking
        if (! method_exists($mod, $method)) {
            // 3.0 introduced camel-cased method names that are translated from a URL
            // segment separated by dashes or underscores
            $method = str_replace('-', '_', $method);
            $words = explode('_', $method);
            $method = strtolower(array_shift($words));
            $words = array_map('ucfirst', $words);
            $method .= implode('', $words);

            if (! method_exists($mod, $method)) {
                if (! $mod instanceof Mcp) {
                    show_404();
                }
            }
        }

        if ($mod instanceof Mcp && ! method_exists($mod, $method)) {
            $_module_cp_body = $mod->setAddonName($addon)->route($originalMethod, $parameters);
        } else {
            $_module_cp_body = call_user_func_array(array($mod, $method), $parameters);
        }

        // unset reference
        ee()->remove('_mcp_reference');

        // remove package paths
        ee()->load->remove_package_path($info->getPath());

        return $_module_cp_body;
    }

    private function getExtensionSettings($name)
    {
        if (ee()->config->item('allow_extensions') != 'y') {
            show_error(lang('unauthorized_access'), 403);
        }

        $addon = ee()->security->sanitize_filename(strtolower($name));

        $extension = $this->getExtension($addon);

        if (empty($extension) || $extension['installed'] === false) {
            show_error(lang('requested_module_not_installed') . NBS . $addon);
        }

        ee()->lang->loadfile(strtolower($addon));

        $extension_model = ee('Model')->get('Extension')
            ->filter('enabled', 'y')
            ->filter('class', $extension['class'])
            ->first();

        $current = $extension_model->settings;

        $class_name = $extension['class'];
        $OBJ = new $class_name($current);

        if (method_exists($OBJ, 'settings_form') === true) {
            return $OBJ->settings_form($current);
        }

        $vars = array(
            'base_url' => ee('CP/URL')->make('addons/settings/' . $name . '/save'),
            'cp_page_title' => $extension['name'] . ' ' . lang('configuration'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(array())
        );

        $settings = array();

        foreach ($OBJ->settings() as $key => $options) {
            $element = array(
                'title' => $key,
                'desc' => '',
                'fields' => array()
            );

            if (isset($current[$key])) {
                $value = $current[$key];
            } elseif (is_array($options)) {
                $value = $options[2];
            } elseif (is_string($options)) {
                $value = $options;
            } else {
                $value = '';
            }

            $choices = array();
            $selected = '';

            // add field instructions, if they exist
            $element['desc'] = (lang($key . '_desc') != $key . '_desc') ? lang($key . '_desc') : '';

            if (! is_array($options)) {
                $element['fields'][$key] = array(
                    'type' => 'text',
                    'value' => str_replace("\\'", "'", $value),
                );
                $vars['sections'][0][] = $element;

                continue;
            }

            switch ($options[0]) {
                case 's':
                    // Select fields
                    foreach ($options[1] as $k => $v) {
                        $choices[$k] = lang($v);
                    }

                    $element['fields'][$key] = array(
                        'type' => 'radio',
                        'value' => $value,
                        'choices' => $choices,
                        'no_results' => [
                            'text' => 'no_rows_returned'
                        ]
                    );

                    break;

                case 'r':
                    // Radio buttons
                    foreach ($options[1] as $k => $v) {
                        $choices[$k] = lang($v);
                    }

                    $element['fields'][$key] = array(
                        'type' => 'radio',
                        'value' => $value,
                        'choices' => $choices,
                        'no_results' => [
                            'text' => 'no_rows_returned'
                        ]
                    );

                    break;

                case 'ms':
                case 'c':
                    // Multi select & Checkboxes
                    foreach ($options[1] as $k => $v) {
                        $choices[$k] = lang($v);
                    }

                    $element['fields'][$key] = array(
                        'type' => 'checkbox',
                        'value' => $value,
                        'choices' => $choices,
                        'no_results' => [
                            'text' => 'no_rows_returned'
                        ]
                    );

                    break;

                case 't':
                    // Textareas
                    $element['fields'][$key] = array(
                        'type' => 'textarea',
                        'value' => str_replace("\\'", "'", $value),
                        'kill_pipes' => isset($options['1']['kill_pipes']) ? $options['1']['kill_pipes'] : false
                    );

                    break;

                case 'i':
                    // Input fields
                    $element['fields'][$key] = array(
                        'type' => 'text',
                        'value' => str_replace("\\'", "'", $value),
                    );

                    break;
            }

            $vars['sections'][0][] = $element;
        }

        return ee('View')->make('_shared/form_with_box')->render($vars);
    }

    private function saveExtensionSettings($name)
    {
        if (ee()->config->item('allow_extensions') != 'y') {
            show_error(lang('unauthorized_access'), 403);
        }

        $addon = ee()->security->sanitize_filename(strtolower($name));

        $extension = $this->getExtension($addon);

        if (empty($extension) || $extension['installed'] === false) {
            show_error(lang('requested_module_not_installed') . NBS . $addon);
        }

        ee()->lang->loadfile(strtolower($addon));

        $class_name = $extension['class'];
        $OBJ = new $class_name();

        if (method_exists($OBJ, 'settings_form') === true) {
            return $OBJ->save_settings();
        }

        $settings = array();

        foreach ($OBJ->settings() as $key => $value) {
            if (! is_array($value)) {
                $settings[$key] = (ee()->input->post($key) !== false) ? ee()->input->get_post($key) : $value;
            } elseif (is_array($value) && isset($value['1']) && is_array($value['1'])) {
                if (is_array(ee()->input->post($key)) or $value[0] == 'ms' or $value[0] == 'c') {
                    $data = (is_array(ee()->input->post($key))) ? ee()->input->get_post($key) : array();

                    $data = array_intersect($data, array_keys($value['1']));
                } else {
                    if (ee()->input->post($key) === false) {
                        $data = (! isset($value['2'])) ? '' : $value['2'];
                    } else {
                        $data = ee()->input->post($key);
                    }
                }

                $settings[$key] = $data;
            } else {
                $settings[$key] = (ee()->input->post($key) !== false) ? ee()->input->get_post($key) : '';
            }
        }

        $extension_model = ee('Model')->get('Extension')
            ->filter('enabled', 'y')
            ->filter('class', $extension['class'])
            ->all();

        $extension_model->settings = $settings;
        $extension_model->save();

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('settings_saved'))
            ->addToBody(sprintf(lang('settings_saved_desc'), $extension['name']))
            ->defer();
    }

    private function getFieldtypeSettings($fieldtype)
    {
        if (! ee('Permission')->can('access_addons')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->api_channel_fields->fetch_installed_fieldtypes();
        $FT = ee()->api_channel_fields->setup_handler($fieldtype['package'], true);

        $FT->settings = isset($fieldtype['settings']) ? $fieldtype['settings'] : [];

        $fieldtype_settings = ee()->api_channel_fields->apply('display_global_settings');

        if (is_array($fieldtype_settings)) {
            $vars = array(
                'base_url' => ee('CP/URL')->make('addons/settings/' . $fieldtype['package'] . '/save'),
                'cp_page_title' => $fieldtype['name'] . ' ' . lang('configuration'),
                'save_btn_text' => 'btn_save_settings',
                'save_btn_text_working' => 'btn_saving',
                'sections' => array(array($fieldtype_settings))
            );

            return ee('View')->make('_shared/form')->render($vars);
        } else {
            $html = '<div class="box">';
            $html .= '<h1>' . $fieldtype['name'] . ' ' . lang('configuration') . '</h1>';
            $html .= form_open(ee('CP/URL')->make('addons/settings/' . $fieldtype['package'] . '/save'), 'class="settings"');
            $html .= ee('CP/Alert')->get('shared-form');
            $html .= $fieldtype_settings;
            $html .= '<fieldset class="form-ctrls">';
            $html .= cp_form_submit('btn_save_settings', 'btn_saving');
            $html .= '</fieldset>';
            $html .= form_close();
            $html .= '</div>';

            return $html;
        }
    }

    private function saveFieldtypeSettings($fieldtype)
    {
        if (! ee('Permission')->can('access_addons')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->api_channel_fields->fetch_installed_fieldtypes();
        $FT = ee()->api_channel_fields->setup_handler($fieldtype['package'], true);

        $FT->settings = $fieldtype['settings'];

        $settings = ee()->api_channel_fields->apply('save_global_settings');

        $fieldtype_model = ee('Model')->get('Fieldtype')
            ->filter('name', $fieldtype['package'])
            ->first();

        $fieldtype_model->settings = $settings;
        $fieldtype_model->save();

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('settings_saved'))
            ->addToBody(sprintf(lang('settings_saved_desc'), $fieldtype['name']))
            ->defer();
    }

    /**
     * Wraps the major version number in a <b> tag
     *
     * @param  string  $version    The version number
     * @return string              The formatted version number
     */
    private function formatVersionNumber($version)
    {
        if (strpos($version, '.') === false) {
            return $version;
        }

        $parts = explode('.', $version);
        $parts[0] = '<b>' . $parts[0] . '</b>';

        return implode('.', $parts);
    }

    private function assertUserHasAccess($addon)
    {
        if (ee('Permission')->isSuperAdmin()) {
            return;
        }

        $module = $this->getModule($addon);

        if (
            ! isset($module['module_id'])
            || ! in_array($module['module_id'], $this->assigned_modules)
        ) {
            show_error(lang('unauthorized_access'), 403);
        }
    }
}

// EOF
