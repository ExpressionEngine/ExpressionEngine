<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Dock;

use ExpressionEngine\Service\View\ViewFactory;

/**
 * Dock Factory
 */
class DockFactory
{
    /**
     * @var array $prolets The prolets in this dock
     */
    protected $prolets = [];

    /**
     * @var string $class Any extra classes to apply to the containing div
     */
    protected $class;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Syntactic sugar ¯\_(ツ)_/¯
     */
    public function make()
    {
        return $this;
    }

    /**
     * Build the output that would include the Pro stuff
     *
     * @return string $output
     */
    public function buildOutput($output)
    {
        if (
            REQ == 'PAGE' &&
            ee()->session->userdata('member_id') != 0 &&
            ee()->session->userdata('admin_sess') == 1 &&
            (ee()->config->item('enable_dock') == 'y' || ee()->config->item('enable_dock') === false)
        ) {
            if (isset(ee()->TMPL) && is_object(ee()->TMPL) && in_array(ee()->TMPL->template_type, ['webpage'])) {
                /*
                    At the minimum, we check following:
                    - License is valid
                    - Member has access to the dock
                    - Member has access to frontedit feature
                */
                $proAccess = ee('pro:Access');
                if ((!ee('pro:Access')->requiresValidLicense() || $proAccess->hasValidLicense()) && $proAccess->hasDockPermission()) {
                    // enable frontedit and load required assets
                    ee('pro:FrontEdit')->ensureEntryId();
                    if (ee()->input->cookie('frontedit') != 'off' && $proAccess->hasAnyFrontEditPermission()) {
                        $output = ee('pro:FrontEdit')->loadFrontEditAssets($output);
                    }
                    if (AJAX_REQUEST) {
                        ee()->load->library('javascript');
                        $js = "if (window.EE && window.EE.pro) { window.EE.pro.refresh() }";
                        $output = ee()->output->add_to_foot($output, ee()->javascript->inline($js));
                    } else {
                        $output = $this->buildDock($output);
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Build the JS necessary to create Dock on frontend
     *
     * @return string Javascript object
     */
    public function buildDock($output)
    {
        ee()->TMPL->log_item("Pro: Building the Dock.");
        ee()->lang->load('cp');
        ee()->lang->load('pro');

        // Get version of asset cache busting
        $addon = ee('Addon')->get('pro');
        $version = $addon->getInstalledVersion();
        $cacheBuster = time();

        $assets = '<style type="text/css">[v-cloak]{display: none;}</style>';
        $assets = '<style type="text/css">@media print {div[id^="ee-pro"],span.eeFrontEdit {display: none !important;}}</style>';
        $assets .= '<div id="ee-44E4F0E59DFA295EB450397CA40D1169" v-cloak></div>';
        $assets .= '<script type="text/javascript" src="' . URL_PRO_THEMES . 'js/fronteditor.min.js?v=' . $version . '-' . $cacheBuster .'"></script>';
        $assets .= '<link rel="stylesheet" type="text/css"  media="screen" href="' . URL_PRO_THEMES . 'css/fronteditor.min.css?v=' . $version . '-' . $cacheBuster .'" />';
        $output = ee()->output->add_to_foot($output, $assets);

        ee()->load->library('javascript');
        $fullpageEditUrl = ee('CP/URL')->make(
            'publish/edit/entry/ENTRY_ID',
            [
                'site_id' => 'SITE_ID',
                'preview' => 'y',
                'hide_closer' => 'y',
                'return' => urlencode(ee()->functions->fetch_current_uri())
            ],
            ee()->config->item('cp_url')
        )->compile();
        $frontEditState = ee()->input->cookie('frontedit') == 'off' ? 'off' : 'on';
        if (
            (ee()->config->item('enable_frontedit') !== false && ee()->config->item('enable_frontedit') != 'y')
            || ! ee('pro:Access')->hasAnyFrontEditPermission()
            || ee()->TMPL->enable_frontedit == 'n'
        ) {
            $frontEditState = 'disabled';
        }
        $globals = [
            'pro.cp_url' => ee()->config->item('cp_url'),
            'pro.fullpage_url' => $fullpageEditUrl,
            'pro.themes_url' => URL_PRO_THEMES,
            'pro.version' => $version,
            'pro.login_url' => ee('CP/URL')->make('pro/authenticate', ['hide_closer' => 'y'], ee()->config->item('cp_url'))->compile(),
            'pro.frontedit' => $frontEditState,
            'pro.lang' => [
                'save' => lang('save'),
                'edit_in_full_form' => lang('edit_in_full_form'),
                'modal_is_dirty_close_confirmation' => lang('modal_is_dirty_close_confirmation'),
                'edit' => lang('edit'),
                'edit_mode' => lang('edit_mode'),
                'view_cp' => lang('view_cp'),
                'hide_dock' => lang('hide_dock'),
                'save' => lang('save'),
                'cancel' => lang('cancel'),
                'save_without_reload' => lang('save_without_reload'),
                'login' => lang('login'),
                'drag_handle_icon' => lang('drag_handle_icon'),
            ],
            'pro.prolets'=> [],
            'pro.actions'=> []
        ];

        $proActionsQuery = ee()->db->select('action_id, method')->where('class', 'Pro')->or_where(['class' => 'File', 'method' => 'addonIcon'])->get('actions');
        foreach ($proActionsQuery->result_array() as $row) {
            $globals['pro.actions'][$row['method']] = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $row['action_id'];
        }

        $dock = ee('Model')->get('pro:Dock')->first();
        if (empty($dock)) {
            $dock = ee('Model')->make('pro:Dock');
            $dock->save();
        }

        //get some global params for prolets
        $globalParams = [
            'current_uri' => urlencode(ee()->functions->fetch_current_uri()),
            'hide_closer' => 'y'
        ];
        if (isset(ee()->session->cache['channel']['entry_ids'])) {
            $globalParams['entry_id'] = reset(ee()->session->cache['channel']['entry_ids']);
        }

        //instantiate prolet class
        $addons = [];
        $license = ee('pro:License');
        $assigned_modules = ee()->session->getMember()->getAssignedModules()->pluck('module_name');
        foreach ($dock->Prolets as $proletModel) {
            if (empty($proletModel->source)) {
                continue;
            }
            //each add-on can currently have one prolet
            if (isset($addons[$proletModel->source])) {
                continue;
            } else {
                $addon = $proletModel->addon;
            }

            //because of how permissions are built, currently prolets require module
            if (
                !empty($addon) // add-ons present on system
                && $addon->isInstalled() // installed
                && $addon->hasModule() // contains module
                && (ee('Permission')->isSuperAdmin() || in_array($addon->getModuleClass(), $assigned_modules)) // the user has access to the module
                && $addon->hasProlet() // prolet file exists
                && !empty($proletModel->prolet) // and contains valid prolet class
                && $proletModel->prolet->checkPermissions() // and user is passing prolet's own checks
                && $license->isRegisteredProlet($proletModel->source, $proletModel->class) //and licensed accordingly
            ) {
                //make sure prolet is initialized
                if ($addon::implementsInitializableProletInterface($proletModel->class) && !isset(ee()->session->cache['pro::' . $proletModel->source . '::' . $proletModel->class])) {
                    continue;
                }
                //set up prolet params
                $proletParams = [];
                if (isset(ee()->session->cache['pro::' . $proletModel->source . '::' . $proletModel->class])) {
                    foreach (ee()->session->cache['pro::' . $proletModel->source . '::' . $proletModel->class] as $key => $value) {
                        $proletParams[$key] = $value;
                    }
                }
                //make sure requirements are met
                $url = $proletModel->getUrl(array_merge($globalParams, $proletParams));
                if (in_array($proletModel->method, ['ajax', 'redirect']) && empty($url)) {
                    continue;
                }
                //add prolet to the JS object
                $globals['pro.prolets'][$proletModel->source . '--' . $proletModel->class] = [
                    'icon'   => $globals['pro.actions']['addonIcon'] . $proletModel->getIconUrl(false),
                    'name'   => $proletModel->name,
                    'method' => $proletModel->method,
                    'size'   => $proletModel->size,
                    'url'    => $url,
                    'buttons'=> $proletModel->buttons
                ];
            }
            $addons[$proletModel->source] = $addon;
        }

        ee()->javascript->set_global($globals);
        $output = ee()->output->add_to_foot($output, ee()->javascript->get_global());

        return $output;
    }
}
// EOF
