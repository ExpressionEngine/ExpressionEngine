<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Homepage;

use CP_Controller;

/**
 * Homepage Controller
 */
class Homepage extends CP_Controller
{
    public function index()
    {
        $this->redirectIfNoSegments();

        ee('CP/Alert')->makeDeprecationNotice()->now();

        $member = ee()->session->getMember();
        $role_ids = $member->getAllRoles()->pluck('role_id');

        $dashboard_layout = ee('Model')->get('DashboardLayout')
            ->filter('member_id', $member->member_id)
            ->orFilter('role_id', 'IN', $role_ids)
            ->first();
        if (empty($dashboard_layout)) {
            $dashboard_layout = ee('Model')->make('DashboardLayout');
        }

        $vars = [
            'header' => [
                'title' => ee()->config->item('site_name'),
            ],
            'dashboard' => $dashboard_layout->generateDashboardHtml()
        ];

        if (ee('pro:Access')->hasRequiredLicense()) {
            $vars['header']['toolbar_items'] = array(
                'settings' => array(
                    'href' => ee('CP/URL')->make('pro/dashboard/layout/' . $member->member_id),
                    'title' => lang('edit_dashboard_layout'),
                )
            );
        }

        if (bool_config_item('share_analytics')) {
            require_once(APPPATH . 'libraries/El_pings.php');
            $pings = new \El_pings();
            $pings->shareAnalytics();
        }

        ee()->view->cp_page_title = ee()->config->item('site_name') . ' ' . lang('overview');

        ee()->cp->render('homepage', $vars);
    }

    /**
     * If we arrive to this controller's index as a result of being the default
     * controller, check to see if there is a default homepage we should be
     * redirecting to instead
     */
    private function redirectIfNoSegments()
    {
        if (empty(ee()->uri->segments)) {
            $member_home_url = ee()->session->getMember()->getCPHomepageURL();

            if ($member_home_url->path != 'homepage') {
                // Preserve updater result status messages
                if (ee('Request')->get('update')) {
                    $member_home_url->setQueryStringVariable(
                        'update',
                        ee('Request')->get('update')
                    );
                }

                ee()->session->benjaminButtonFlashdata();

                ee()->functions->redirect($member_home_url);
            }
        }
    }

    public function acceptChecksums()
    {
        if (! ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        $return = ee('CP/URL')->make('homepage');

        if (ee()->input->post('return')) {
            ee()->load->library('file_integrity');
            $changed = ee()->file_integrity->check_bootstrap_files(true);

            if ($changed) {
                foreach ($changed as $site_id => $paths) {
                    foreach ($paths as $path) {
                        ee()->file_integrity->create_bootstrap_checksum($path, $site_id);
                    }
                }
            }

            $return = ee('CP/URL')->decodeUrl(ee()->input->post('return'));
        }

        ee()->functions->redirect($return);
    }

    /**
     * Records that the changelog for this version of EE has been viewed by
     * this member, and then redirects to the changelog.
     */
    public function showChangelog()
    {
        $news_view = ee('Model')->get('MemberNewsView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->first();

        if (! $news_view) {
            $news_view = ee('Model')->make(
                'MemberNewsView',
                ['member_id' => ee()->session->userdata('member_id')]
            );
        }

        $news_view->version = APP_VER;
        $news_view->save();

        ee()->functions->redirect(
            ee()->cp->makeChangelogLinkForVersion(APP_VER)
        );
    }

    /**
     * Sets the CP view mode (with full menu or jump menu)
     */
    public function setViewmode()
    {
        $viewmode = ee()->input->post('ee_cp_viewmode');
        if (in_array($viewmode, ['classic', 'jumpmenu'])) {
            ee()->input->set_cookie('ee_cp_viewmode', $viewmode, 31104000);
        }
        ee()->functions->redirect(ee('CP/URL')->make('homepage'));
    }

    /**
     * Toggles the viewmode to the opposite setting.
     *
     * @return void
     */
    public function toggleViewmode()
    {
        $viewmode = ee()->input->cookie('ee_cp_viewmode');

        // If it doesn't exist, or it's set to classic, flip the sidebar off.
        if (empty($viewmode) || $viewmode == 'classic') {
            $viewmode = 'jumpmenu';
        } else {
            $viewmode = 'classic';
        }

        ee()->input->set_cookie('ee_cp_viewmode', $viewmode, 31104000);

        ee()->functions->redirect(ee('CP/URL')->make('homepage'));
    }

    /**
     * Toggles the sidebar navigation to/from collapsed state
     *
     * @return void
     */
    public function toggleSidebarNav()
    {
        ee()->input->set_cookie('collapsed_nav', (int) ee()->input->get('collapsed'), 31104000);

        ee()->output->send_ajax_response(['success']);
    }

    /**
     * Toggles the secondary sidebar navigation to/from collapsed state
     *
     * @return void
     */
    public function toggleSecondarySidebarNav()
    {
        if (empty(ee('Request')->get('owner'))) {
            ee()->output->send_ajax_response(['error']);
        }
        $state = json_decode(ee()->input->cookie('secondary_sidebar'));
        if (is_null($state)) {
            $state = new \stdClass();
        }
        $owner = ee('Security/XSS')->clean(ee('Request')->get('owner'));
        $state->$owner = (int) ee()->input->get('collapsed');
        ee()->input->set_cookie('secondary_sidebar', json_encode($state), 31104000);

        ee()->output->send_ajax_response(['success']);
    }

    public function dismissBanner()
    {
        $member = ee()->session->getMember();
        $member->dismissed_banner = 'y';
        $member->save();

        ee()->output->send_ajax_response(['success']);
    }
}

// EOF
