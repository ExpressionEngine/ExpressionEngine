<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members\Profile;

use CP_Controller;
use ExpressionEngine\Library\CP;
use ExpressionEngine\Library\CP\Table;

/**
 * Members Profile Controller
 */
class Profile extends CP_Controller
{
    protected $query_string;
    protected $member;
    private $base_url = 'members/profile/settings';
    protected $breadcrumbs;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee()->lang->loadfile('settings');
        ee()->lang->loadfile('myaccount');
        ee()->lang->loadfile('members');

        // check permissions everywhere except for this landing page controller,
        // which redirects in its index function
        if (ee()->uri->segments != array(1 => 'cp', 2 => 'members', 3 => 'profile')) {
            $this->permissionCheck();
        }

        $id = ee()->input->get('id');

        if (empty($id)) {
            $id = ee()->session->userdata['member_id'];
        }

        $qs = array('id' => $id);
        if (ee('Request')->get('modal_form') == 'y') {
            $qs['modal_form'] = 'y';
        }
        $this->query_string = $qs;
        $this->base_url = ee('CP/URL')->make('members/profile/settings');
        $this->base_url->setQueryStringVariable('id', $id);
        $this->member = ee('Model')->get('Member', $id)->with('PrimaryRole', 'Roles', 'RoleGroups')->all()->first();

        if (is_null($this->member)) {
            show_404();
        }

        if ($this->member->isSuperAdmin() && ! ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->load->library('form_validation');

        if (ee('Request')->get('modal_form') == 'y') {
            $this->generateMinimalSidebar();
        } else {
            $this->generateSidebar();
        }

        ee()->javascript->set_global([
            'cp.validatePasswordUrl' => ee('CP/URL', 'login/validate_password')->compile(),
            'lang.password_icon' => lang('password_icon')
        ]);

        $this->breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/profile', $qs)->compile() => $this->member->screen_name
        );

        ee()->view->header = array(
            'title' => $this->member->username
        );
    }

    protected function permissionCheck()
    {
        if (! ee('Permission')->hasAll('can_access_members', 'can_edit_members')) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    protected function generateMinimalSidebar($active = null)
    {
        $sidebar = ee('CP/Sidebar')->make();

        $header = $sidebar->addHeader(!empty($this->member->screen_name) ? $this->member->screen_name : $this->member->username);

        $list = $header->addBasicList();

        $list->addItem(lang('personal_settings'), ee('CP/URL')->make('members/profile/settings', $this->query_string));
        $list->addItem(lang('email_settings'), ee('CP/URL')->make('members/profile/email', $this->query_string));
        $list->addItem(lang('auth_settings'), ee('CP/URL')->make('members/profile/auth', $this->query_string));

        $sa_editing_self = ($this->member->isSuperAdmin() && $this->member->member_id == ee()->session->userdata['member_id']);
        $group_locked = (! ee('Permission')->isSuperAdmin() && $this->member->PrimaryRole->is_locked);

        if (! $sa_editing_self && ! $group_locked) {
            $list->addItem(lang('member_roles'), ee('CP/URL')->make('members/profile/roles', $this->query_string));
        }

    }


    protected function generateSidebar($active = null)
    {
        $sidebar = ee('CP/Sidebar')->make();

        $header = $sidebar->addHeader(lang('account'));

        $list = $header->addBasicList();

        $list->addItem(lang('personal_settings'), ee('CP/URL')->make('members/profile/settings', $this->query_string));

        $list->addItem(lang('email_settings'), ee('CP/URL')->make('members/profile/email', $this->query_string));
        $list->addItem(lang('auth_settings'), ee('CP/URL')->make('members/profile/auth', $this->query_string));

        if ($this->member->member_id == ee()->session->userdata['member_id'] && ee('pro:Access')->hasRequiredLicense() && (ee()->config->item('enable_mfa') === false || ee()->config->item('enable_mfa') === 'y')) {
            ee()->lang->load('pro');
            $list->addItem(lang('mfa'), ee('CP/URL')->make('members/profile/pro/mfa', $this->query_string));
        }

        if (ee()->config->item('allow_member_localization') == 'y' or ee('Permission')->isSuperAdmin()) {
            $list->addItem(lang('date_settings'), ee('CP/URL')->make('members/profile/date', $this->query_string));
        }

        $list->addItem(lang('consents'), ee('CP/URL')->make('members/profile/consent', $this->query_string));

        $list = $sidebar->addHeader(lang('content'))
            ->addBasicList();

        if (ee('Permission')->hasAll('can_access_members', 'can_edit_members')) {
            $list->addItem(lang('publishing_settings'), ee('CP/URL')->make('members/profile/publishing', $this->query_string));
        }

        if (ee('Permission')->can('edit_html_buttons')) {
            $url = ee('CP/URL')->make('members/profile/buttons', $this->query_string);
            $item = $list->addItem(lang('html_buttons'), $url);
            if ($url->matchesTheRequestedURI()) {
                $item->isActive();
            }
        }

        $url = ee('CP/URL')->make('members/profile/quicklinks', $this->query_string);
        $item = $list->addItem(lang('quick_links'), $url);
        if ($url->matchesTheRequestedURI()) {
            $item->isActive();
        }

        $url = ee('CP/URL')->make('members/profile/bookmarks', $this->query_string);
        $item = $list->addItem(lang('bookmarklets'), $url);
        if ($url->matchesTheRequestedURI()) {
            $item->isActive();
        }

        $list->addItem(lang('subscriptions'), ee('CP/URL')->make('members/profile/subscriptions', $this->query_string));

        if (ee('Permission')->can('edit_members')) {
            $list = $sidebar->addHeader(lang('administration'))
                ->addBasicList();

            $list->addItem(lang('info_and_activity'), ee('CP/URL')->make('members/profile/activity', $this->query_string));

            $list->addItem(lang('blocked_members'), ee('CP/URL')->make('members/profile/ignore', $this->query_string));

            $sa_editing_self = ($this->member->isSuperAdmin() && $this->member->member_id == ee()->session->userdata['member_id']);
            $group_locked = (! ee('Permission')->isSuperAdmin() && $this->member->PrimaryRole->is_locked);

            if (! $sa_editing_self && ! $group_locked) {
                $list->addItem(lang('member_roles'), ee('CP/URL')->make('members/profile/roles', $this->query_string));
            }

            $list->addItem(lang('access_overview'), ee('CP/URL')->make('members/profile/access', $this->query_string));
            $list->addItem(lang('cp_settings'), ee('CP/URL')->make('members/profile/cp-settings', $this->query_string));

            if ($this->member->member_id != ee()->session->userdata['member_id']) {
                if (! $this->member->isAnonymized()) {
                    $list->addItem(sprintf(lang('email_username'), $this->member->username), ee('CP/URL')->make('utilities/communicate/member/' . $this->member->member_id));
                }

                if (ee('Permission')->isSuperAdmin() && ! $this->member->isAnonymized()) {
                    $list->addItem(sprintf(lang('login_as'), $this->member->username), ee('CP/URL')->make('members/profile/login', $this->query_string));
                }

                if (ee('Permission')->can('delete_members')) {
                    $session = ee('Model')->get('Session', ee()->session->userdata('session_id'))->first();

                    if (! $this->member->isAnonymized()) {
                        $list->addItem(sprintf(lang('anonymize_username'), $this->member->username), ee('CP/URL')->make('members/anonymize', $this->query_string))
                            ->asDeleteAction('modal-confirm-anonymize-member');

                        $modal_vars = [
                            'name' => 'modal-confirm-anonymize-member',
                            'title' => sprintf(lang('anonymize_username'), lang('member')),
                            'alert' => ee('CP/Alert')
                                ->makeInline()
                                ->asIssue()
                                ->addToBody(lang('anonymize_member_desc'))
                                ->render(),
                            'form_url' => ee('CP/URL')->make('members/anonymize'),
                            'button' => [
                                'text' => lang('btn_confirm_and_anonymize'),
                                'working' => lang('btn_confirm_and_anonymize_working')
                            ],
                            'checklist' => [
                                [
                                    'kind' => lang('member'),
                                    'desc' => $this->member->username,
                                ]
                            ],
                            'hidden' => [
                                'bulk_action' => 'anonymize',
                                'selection' => $this->member->member_id
                            ]
                        ];

                        if (! $session->isWithinAuthTimeout()) {
                            $modal_vars['secure_form_ctrls'] = [
                                'title' => 'your_password',
                                'desc' => 'your_password_anonymize_members_desc',
                                'group' => 'verify_password',
                                'fields' => [
                                    'verify_password' => [
                                        'type' => 'password',
                                        'required' => true,
                                        'maxlength' => PASSWORD_MAX_LENGTH
                                    ]
                                ]
                            ];
                        }

                        ee('CP/Modal')->addModal('anonymize', ee('View')->make('_shared/modal_confirm_remove')->render($modal_vars));
                    }

                    $list->addItem(sprintf(lang('delete_username'), $this->member->username), ee('CP/URL')->make('members/delete', $this->query_string))
                        ->asDeleteAction('modal-confirm-remove-member');

                    // If they have entries assigned, set up the markup in the deletion modal for reassignment
                    $heirs_view = '';
                    if (ee('Model')->get('ChannelEntry')->filter('author_id', $this->member->getId())->count() > 0) {
                        $role_ids = array(1, $this->member->role_id);

                        $vars['heirs'] = ee('Member')->getAuthors();
                        if (array_key_exists($this->member->getId(), $vars['heirs'])) {
                            unset($vars['heirs'][$this->member->getId()]);
                        }

                        $vars['selected'] = array($this->member->getId());

                        $vars['fields'] = array(
                            'heir' => array(
                                'type' => 'radio',
                                'choices' => $vars['heirs'],
                                'filter_url' => ee('CP/URL')->make(
                                    'members/heir-filter',
                                    [
                                        'role_ids' => implode('|', $role_ids),
                                        'selected' => $this->member->getId()
                                    ]
                                )->compile(),
                                'no_results' => ['text' => 'no_members_found'],
                                'margin_top' => true,
                                'margin_left' => true
                            )
                        );

                        $heirs_view = ee('View')->make('members/delete_confirm')->render($vars);
                    }

                    $modal_vars = array(
                        'name' => 'modal-confirm-remove-member',
                        'form_url' => ee('CP/URL')->make('members/delete'),
                        'checklist' => array(
                            array(
                                'kind' => lang('members'),
                                'desc' => $this->member->username,
                            )
                        ),
                        'hidden' => array(
                            'bulk_action' => 'remove',
                            'selection' => $this->member->member_id
                        ),
                        'ajax_default' => $heirs_view
                    );

                    if (! $session->isWithinAuthTimeout()) {
                        $modal_vars['secure_form_ctrls'] = [
                            'title' => 'your_password',
                            'desc' => 'your_password_delete_members_desc',
                            'group' => 'verify_password',
                            'fields' => [
                                'verify_password' => [
                                    'type' => 'password',
                                    'required' => true,
                                    'maxlength' => PASSWORD_MAX_LENGTH
                                ]
                            ]
                        ];
                    }

                    ee('CP/Modal')->addModal('member', ee('View')->make('_shared/modal_confirm_remove')->render($modal_vars));
                }
            }
        }
    }

    public function index()
    {
        ee()->functions->redirect($this->base_url);
    }

    /**
     * Generic method for saving member settings given an expected array
     * of fields.
     *
     * @param	array	$sections	Array of sections passed to form view
     * @return	bool	Success or failure of saving the settings
     */
    protected function saveSettings($sections)
    {
        // Make sure we're getting only the fields we asked for
        foreach ($sections as $settings) {
            foreach ($settings as $setting) {
                if (! empty($setting['fields']) && is_array($setting['fields'])) {
                    foreach ($setting['fields'] as $field_name => $field) {
                        $post = ee()->input->post($field_name);

                        // Handle arrays of checkboxes as a special case;
                        if ($field['type'] == 'checkbox') {
                            foreach ($field['choices']  as $property => $label) {
                                $this->member->$property = in_array($property, (array) $post) ? 'y' : 'n';
                            }
                        } else {
                            if ($post !== false) {
                                $this->member->$field_name = $post;
                            }
                        }

                        $name = str_replace('m_field_id_', 'm_field_ft_', $field_name);

                        // Set custom field format override if available, too
                        if (strpos($name, 'field_ft_') !== false && ee()->input->post($name)) {
                            $this->member->$name = ee()->input->post($name);
                        }

                        $name = str_replace('m_field_id_', 'm_field_dt_', $field_name);

                        // Set custom field format override if available, too
                        if (strpos($name, 'field_dt_') !== false && ee()->input->post($name)) {
                            $this->member->$name = ee()->input->post($name);
                        }
                    }
                }
            }
        }

        $validated = $this->member->validate();

        if ($response = $this->ajaxValidation($validated)) {
            return $response;
        }

        if ($validated->isNotValid()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('member_not_updated'))
                ->addToBody(lang('member_not_updated_desc'))
                ->now();

            ee()->lang->load('content');
            ee()->view->errors = $validated;

            return false;
        }

        $this->member->save();

        return true;
    }
}
// END CLASS

// EOF
