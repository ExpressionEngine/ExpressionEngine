<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members;

use CP_Controller;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Member\Member;
use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Library\CP\MemberManager\ColumnFactory;
use ExpressionEngine\Library\CP\MemberManager\ColumnRenderer;

/**
 * Members Controller
 */
class Members extends CP_Controller
{
    public $base_url;

    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_members')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('members');

        $this->base_url = ee('CP/URL')->make('members');

        ee()->javascript->set_global([
            'cp.validatePasswordUrl' => ee('CP/URL', 'login/validate_password')->compile(),
            'lang.password_icon' => lang('password_icon')
        ]);
    }

    public function index()
    {
        $action = ee('Request')->post('bulk_action');

        if ($action) {
            $ids = ee('Request')->post('selection');
            switch ($action) {
                case 'remove':
                    $this->delete($ids);
                    break;

                case 'approve':
                    $this->approve($ids);
                    break;

                case 'decline':
                    $this->decline($ids);
                    break;

                case 'resend':
                    $this->resend($ids);
                    break;
            }

            ee()->functions->redirect($this->base_url);
        }

        $vars = $this->listingsPage(null);

        ee()->javascript->set_global('lang.remove_confirm', lang('members') . ': <b>### ' . lang('members') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array('cp/confirm_remove', 'cp/members/members'),
        ));

        if (! ee('Session')->isWithinAuthTimeout()) {
            $vars['confirm_remove_secure_form_ctrls'] = [
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

        $vars['cp_heading'] = lang('all_members');
        $vars['toolbar_items'] = [];
        if (ee('Permission')->can('edit_member_fields')) {
            $vars['toolbar_items']['fields'] = [
                'href' => ee('CP/URL')->make('settings/member-fields'),
                'class' => 'button--secondary fal fa-pen-field',
                'title' => lang('custom_member_fields')
            ];
        }
        if (ee('Permission')->can('access_sys_prefs')) {
            $vars['toolbar_items']['settings'] = [
                'href' => ee('CP/URL')->make('settings/members'),
                'class' => 'button--secondary icon--settings',
                'title' => lang('member_settings')
            ];
        }
        if (ee('Permission')->can('create_members')) {
            $vars['toolbar_items']['action_button'] = [
                'content' => lang('new_member'),
                'href' => ee('CP/URL')->make('members/create')
            ];
        }

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('all_members');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('members')
        );

        if (AJAX_REQUEST) {
            return array(
                'html' => ee('View')->make('members/index')->render($vars),
                'url' => $vars['form_url']->compile(),
                'viewManager_saveDefaultUrl' => ee('CP/URL')->make('members/views/save-default', ['role_id' => $vars['role_id']])->compile()
            );
        }

        ee()->cp->render('members/index', $vars);
    }

    /**
     * Decline pending members
     *
     * @param array $ids The ID(s) of the member(s) being approved
     * @return void
     */
    private function decline(array $ids)
    {
        if (! ee('Permission')->can('delete_members')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $members = ee('Model')->get('Member', $ids)
            ->fields('member_id', 'username', 'screen_name', 'email', 'role_id')
            ->filter('role_id', 4)
            ->all();

        if (ee()->config->item('declined_member_notification') == 'y') {
            $template = ee('Model')->get('SpecialtyTemplate')
                ->filter('template_name', 'decline_member_validation')
                ->first();

            foreach ($members as $member) {
                $this->pendingMemberNotification($template, $member);
            }
        }

        $usernames = $members->pluck('username');
        $single = ($members->count() == 1);
        $members->delete();

        /* -------------------------------------------
        /* 'cp_members_validate_members' hook.
        /*  - Additional processing when member(s) are validated in the CP
        /*  - Added 1.5.2, 2006-12-28
        */
        ee()->extensions->call('cp_members_validate_members', $ids);
        if (ee()->extensions->end_script === true) {
            return;
        }
        /*
        /* -------------------------------------------*/

        // Update
        ee()->stats->update_member_stats();

        if ($single) {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('member_declined_success'))
                ->addToBody(sprintf(lang('member_declined_success_desc'), $usernames[0]))
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('members_declined_success'))
                ->addToBody(lang('members_declined_success_desc'))
                ->addToBody($usernames)
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make('members', ['role_filter' => Member::PENDING]));
    }

    /**
     * Resend activation emails for pending members
     *
     * @param array $ids The ID(s) of the member(s) being approved
     * @return void
     */
    public function resend(array $ids)
    {
        if (! ee('Permission')->can('edit_members') || ee()->config->item('req_mbr_activation') !== 'email') {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($ids)) {
            $ids = array($ids);
        }

        $members = ee('Model')->get('Member', $ids)
            ->fields('member_id', 'username', 'screen_name', 'email', 'role_id', 'authcode')
            ->filter('role_id', 4)
            ->all();

        $template = ee('Model')->get('SpecialtyTemplate')
            ->filter('template_name', 'mbr_activation_instructions')
            ->first();

        $action_id = ee()->functions->fetch_action_id('Member', 'activate_member');

        foreach ($members as $member) {
            $swap = array(
                'email' => $member->email,
                'activation_url' => ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . $action_id . '&id=' . $member->authcode
            );

            if (!$this->pendingMemberNotification($template, $member, $swap)) {
                $debug_msg = ee()->email->print_debugger(array());
                show_error(lang('error_sending_email') . BR . BR . $debug_msg);
            }
        }

        if ($members->count() == 1) {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('member_activation_resent_success'))
                ->addToBody(sprintf(lang('member_activation_resent_success_desc'), $member->username))
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('member_activation_resent_success'))
                ->addToBody(lang('members_activation_resent_success_desc'))
                ->addToBody($members->pluck('username'))
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make('members', ['role_filter' => Member::PENDING]));
    }

    /**
     * Sends an email to a member based on a provided template.
     *
     * @param ExpressionEngine\Model\Template\SpecialtyTemplate $template The email template
     * @param ExpressionEngine\Model\Member\Member $member The member to be emailed
     * @return bool true of the email sent, false if it did not
     */
    private function pendingMemberNotification($template, $member, array $extra_swap = array())
    {
        ee()->load->library('email');
        ee()->load->helper('text');

        $swap = array(
            'name' => $member->getMemberName(),
            'site_name' => stripslashes(ee()->config->item('site_name')),
            'site_url' => ee()->config->item('site_url'),
            'username' => $member->username,
            ) + $extra_swap;

        $email_title = ee()->functions->var_swap($template->data_title, $swap);
        $email_message = ee()->functions->var_swap($template->template_data, $swap);

        ee()->email->wordwrap = true;
        ee()->email->mailtype = ee()->config->item('mail_format');
        ee()->email->from(
            ee()->config->item('webmaster_email'),
            ee()->config->item('webmaster_name')
        );
        ee()->email->to($member->email);
        ee()->email->subject($email_title);
        ee()->email->message(entities_to_ascii($email_message));
        return ee()->email->send();
    }

    public function banned()
    {
        ee()->functions->redirect(ee('CP/URL')->make('members', ['role_filter' => Member::BANNED]));
    }

    public function banSettings()
    {
        ee()->functions->redirect(ee('CP/URL')->make('settings/ban'));
    }

    public function fields()
    {
        ee()->functions->redirect(ee('CP/URL')->make('settings/member-fields'));
    }

    public function pending()
    {
        ee()->functions->redirect(ee('CP/URL')->make('members', ['role_filter' => Member::PENDING]));
    }

    /**
     * Generate post re-assignment view if applicable
     *
     * @access public
     * @return void
     */
    public function confirm()
    {
        $vars = array();
        $selected = ee('Request')->post('selection');
        $vars['selected'] = $selected;

        $entries = ee('Model')->get('ChannelEntry')
            ->fields('author_id')
            ->filter('author_id', 'IN', $selected)
            ->count();

        // Do the users being deleted have entries assigned to them?
        // If so, fetch the member names for reassigment
        if ($entries > 0) {
            $group_ids = ee('Model')->get('Member', $selected)
                ->fields('role_id')
                ->all()
                ->pluck('role_id');

            $vars['heirs'] = $this->heirFilter($group_ids, $selected);

            $vars['fields'] = array(
                'heir' => array(
                    'type' => 'radio',
                    'choices' => $vars['heirs'],
                    'filter_url' => ee('CP/URL')->make(
                        'members/heir-filter',
                        [
                            'group_ids' => implode('|', $group_ids),
                            'selected' => implode('|', $selected)
                        ]
                    )->compile(),
                    'no_results' => ['text' => 'no_members_found'],
                    'margin_top' => true,
                    'margin_left' => true
                )
            );
        }

        ee()->view->cp_page_title = lang('delete_member') ;
        ee()->cp->render('members/delete_confirm', $vars);
    }

    /**
     * AJAX endpoint for filtering heir selection
     *
     * @param array $group_ids Group IDs to search
     * @param array $selected Members to exclude from search
     * @return array List of members normalized for SelectField
     */
    public function heirFilter($group_ids = null, $selected = null)
    {
        $selected = $selected ?: explode('|', ee('Request')->get('selected'));

        $search = null;
        if (!empty(ee('Request')->get('search'))) {
            $search = ee('Request')->get('search');
        }
        $authors = ee('Member')->getAuthors($search);

        if (!empty($selected)) {
            foreach ($selected as $selectedMemberId) {
                if (array_key_exists($selectedMemberId, $authors)) {
                    unset($authors[$selectedMemberId]);
                }
            }
        }

        return ee('View/Helpers')->normalizedChoices($authors);
    }

    /**
     * Member Anonymize
     */
    public function anonymize()
    {
        $member_id = ee()->input->post('selection', true);
        $member = ee('Model')->get('Member')
            ->filter('member_id', $member_id)
            ->first();

        if (! ee('Permission')->can('delete_members') || ! $member) {
            show_error(lang('unauthorized_access'), 403);
        }

        $profile_url = ee('CP/URL')->make('members/profile/settings', ['id' => $member_id]);

        if (! ee('Session')->isWithinAuthTimeout()) {
            $validator = ee('Validation')->make();
            $validator->setRules(array(
                'verify_password' => 'required|authenticated'
            ));
            $password_confirm = $validator->validate($_POST);

            if ($password_confirm->failed()) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('member_anonymize_problem'))
                    ->addToBody(lang('invalid_password'))
                    ->defer();

                return ee()->functions->redirect($profile_url);
            }

            ee('Session')->resetAuthTimeout();
        }

        if ($member_id == ee()->session->userdata('member_id')) {
            show_error(lang('can_not_delete_self'));
        }

        $this->_super_admin_delete_check($member_id);

        $member->anonymize();

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('member_anonymize_success'))
            ->addToBody(lang('member_anonymize_success_desc'))
            ->defer();

        ee()->functions->redirect($profile_url);
    }

    public function delete()
    {
        $member_ids = ee('Request')->post('selection', true);

        if (! ee('Permission')->can('delete_members') || ! $member_ids) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! ee('Session')->isWithinAuthTimeout()) {
            $validator = ee('Validation')->make();
            $validator->setRules(array(
                'verify_password' => 'required|authenticated'
            ));
            $password_confirm = $validator->validate($_POST);

            if ($password_confirm->failed()) {
                ee('CP/Alert')->makeInline('view-members')
                    ->asIssue()
                    ->withTitle(lang('member_delete_problem'))
                    ->addToBody(lang('invalid_password'))
                    ->defer();

                return ee()->functions->redirect($this->base_url);
            }

            ee('Session')->resetAuthTimeout();
        }

        if (! is_array($member_ids)) {
            $member_ids = array($member_ids);
        }

        if (in_array(ee()->session->userdata('member_id'), $member_ids)) {
            show_error(lang('can_not_delete_self'));
        }

        // Check to see if they're deleting super admins
        $this->_super_admin_delete_check($member_ids);

        // If we got this far we're clear to delete the members
        // First, assign an heir if we are to do so
        if (ee('Request')->post('heir_action') == 'assign') {
            if (! ee('Request')->post('heir')) {
                show_error(lang('heir_required'));
            }

            $heir = ee('Model')->get('Member', ee('Request')->post('heir'))->first();

            ee()->db->where_in('author_id', $member_ids);
            ee()->db->update('entry_versioning', array('author_id' => $heir->getId()));

            ee()->db->where_in('author_id', $member_ids);
            ee()->db->update('channel_titles', array('author_id' => $heir->getId()));

            ee()->db->where_in('uploaded_by_member_id', $member_ids);
            ee()->db->update('files', array('uploaded_by_member_id' => $heir->getId()));

            ee()->db->where_in('modified_by_member_id', $member_ids);
            ee()->db->update('files', array('modified_by_member_id' => $heir->getId()));

            $heir->updateAuthorStats();
        }

        // If we got this far we're clear to delete the members
        ee('Model')->get('Member')->filter('member_id', 'IN', $member_ids)->delete();

        // Send member deletion notifications
        $this->_member_delete_notifications($member_ids);

        /* -------------------------------------------
        /* 'cp_members_member_delete_end' hook.
        /*  - Additional processing when a member is deleted through the CP
        */
        ee()->extensions->call('cp_members_member_delete_end', $member_ids);
        if (ee()->extensions->end_script === true) {
            return;
        }
        /*
        /* -------------------------------------------*/

        $cp_message = (count($member_ids) == 1) ?
            lang('member_deleted') : lang('members_deleted');

        ee('CP/Alert')->makeInline('view-members')
            ->asSuccess()
            ->withTitle(lang('member_delete_success'))
            ->addToBody($cp_message)
            ->defer();

        ee()->functions->redirect($this->base_url);
    }

    /**
     * Check to see if the members being deleted are super admins. If they are
     * we need to make sure that the deleting user is a super admin and that
     * there is at least one more super admin remaining.
     *
     * @param  Array  $member_ids Array of member_ids being deleted
     * @return void
     */
    private function _super_admin_delete_check($member_ids)
    {
        if (! is_array($member_ids)) {
            $member_ids = array($member_ids);
        }

        $super_admins = 0;
        foreach ($member_ids as $member_id) {
            $member = ee('Model')->get('Member', $member_id)->first();
            if (!ee('Permission')->isSuperAdmin()) {
                if ($member->PrimaryRole->is_locked == 'y') {
                    show_error(lang('must_be_superadmin_to_delete_one'));
                }
            }
            if ($member->role_id == 1) {
                $super_admins++;
            }
        }

        // You can't delete the only Super Admin
        $total_super_admins = ee('Model')->get('Member')->filter('role_id', 1)->count();

        if ($super_admins >= $total_super_admins) {
            show_error(lang('cannot_delete_super_admin'));
        }
    }

    /**
     * Send email notifications to email addresses for the respective member
     * group of the users being deleted
     *
     * @param  Array  $member_ids Array of member_ids being deleted
     * @return void
     */
    private function _member_delete_notifications($member_ids)
    {
        $role_ids = ee('Model')->get('RoleSetting')
            ->fields('role_id', 'mbr_delete_notify_emails')
            ->filter('mbr_delete_notify_emails', '!=', '')
            ->all();

        if (empty($role_ids)) {
            return; // No configured notifications at all
        }

        ee()->load->helper('string');

        $role_ids = $role_ids->indexBy('role_id');

        $members = ee('Model')->get('Member', $member_ids)
            ->with('PrimaryRole', 'Roles', 'RoleGroups')
            ->all();

        foreach ($members as $member) {
            $notify_address = [];

            foreach ($member->getAllRoles(false) as $role) {
                if (isset($role_ids[$role->getId()])) {
                    $notify_address[] = $role_ids[$role->getId()];
                }
            }

            // This member does not belong to a Role with email notifcations
            if (empty($notify_address)) {
                continue;
            }

            $notify_address = implode(',', $notify_address);

            $swap = array(
                'name' => $member->screen_name,
                'email' => $member->email,
                'site_name' => stripslashes(ee()->config->item('site_name'))
            );

            ee()->lang->loadfile('member');
            $email_title = ee()->functions->var_swap(
                lang('mbr_delete_notify_title'),
                $swap
            );
            $email_message = ee()->functions->var_swap(
                lang('mbr_delete_notify_message'),
                $swap
            );

            // No notification for the user themselves, if they're in the list
            if (strpos($notify_address, $member->email) !== false) {
                $notify_address = str_replace($member->email, "", $notify_address);
            }

            // Remove multiple commas
            $notify_address = reduce_multiples($notify_address, ',', true);

            if ($notify_address != '') {
                ee()->load->library('email');
                ee()->load->helper('text');

                foreach (explode(',', $notify_address) as $addy) {
                    ee()->email->EE_initialize();
                    ee()->email->wordwrap = false;
                    ee()->email->from(
                        ee()->config->item('webmaster_email'),
                        ee()->config->item('webmaster_name')
                    );
                    ee()->email->to($addy);
                    ee()->email->reply_to(ee()->config->item('webmaster_email'));
                    ee()->email->subject($email_title);
                    ee()->email->message(entities_to_ascii($email_message));
                    ee()->email->send();
                }
            }
        }
    }

    public function create()
    {
        if (! ee('Permission')->can('create_members')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $qs = [];
        if (ee('Request')->get('modal_form') == 'y') {
            $qs = [
                'modal_form' => 'y'
            ];
        }

        $this->base_url = ee('CP/URL')->make('members/create', $qs);

        $vars['errors'] = null;

        if (! empty($_POST)) {
            $member = ee('Model')->make('Member');

            // Separate validator to validate confirm_password and verify_password
            $validator = ee('Validation')->make();
            $validator->setRules(array(
                'confirm_password' => 'required|matches[password]',
                'verify_password' => 'whenGroupIdIs[' . implode(',', ee('Permission')->rolesThatCan('access_cp')) . ']|authenticated[useAuthTimeout]'
            ));

            $validator->defineRule('whenGroupIdIs', function ($key, $password, $parameters, $rule) {
                // Don't need to validate if a member group without CP access was chosen
                return in_array($_POST['role_id'], $parameters) ? true : $rule->skip();
            });

            $member->set($_POST);

            // Set some other defaults
            $member->screen_name = $_POST['username'];
            $member->ip_address = ee()->input->ip_address();
            $member->join_date = ee()->localize->now;
            $member->language = ee()->config->item('deft_lang');

            $role_groups = !empty(ee('Request')->post('role_groups')) ? ee('Request')->post('role_groups') : array();
            $roles = !empty(ee('Request')->post('roles')) ? ee('Request')->post('roles') : array();
            $roles[ee()->input->post('role_id')] = ee()->input->post('role_id');

            $member->RoleGroups = ee('Model')->get('RoleGroup', $role_groups)->all();
            $member->Roles = ee('Model')->get('Role', $roles)->all();

            $result = $member->validate();
            $password_confirm = $validator->validate($_POST);

            // Add password confirmation failure to main result object
            if ($password_confirm->failed()) {
                $rules = $password_confirm->getFailed();
                foreach ($rules as $field => $rule) {
                    $result->addFailed($field, $rule[0]);
                }
            }

            if ($response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                // Now that we know the password is valid, hash it
                $member->hashAndUpdatePassword($member->password);

                // -------------------------------------------
                // 'cp_members_member_create_start' hook.
                //  - Take over member creation when done through the CP
                //  - Added 1.4.2
                //
                ee()->extensions->call('cp_members_member_create_start');
                if (ee()->extensions->end_script === true) {
                    return;
                }
                //
                // -------------------------------------------

                $member->save();

                // Get a fresh copy of this member model and update statistics for its roles
                if (!bool_config_item('ignore_member_stats')) {
                    ee('Model')->get('Member')->filter('member_id', $member->getId())->first()->updateRoleTotalMembers();
                }

                // -------------------------------------------
                // 'cp_members_member_create' hook.
                //  - Additional processing when a member is created through the CP
                //
                ee()->extensions->call('cp_members_member_create', $member->getId(), $member->getValues());
                if (ee()->extensions->end_script === true) {
                    return;
                }
                //
                // -------------------------------------------

                ee()->logger->log_action(lang('new_member_added') . NBS . $member->username);
                ee()->stats->update_member_stats();

                if (ee('Request')->get('modal_form') == 'y') {
                    $result = [
                        'saveId' => $member->getId(),
                        'item' => [
                            'value' => $member->getId(),
                            'label' => $member->screen_name,
                            'instructions' => $member->username
                        ]
                    ];
                    return $result;
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('member_created'))
                    ->addToBody(sprintf(lang('member_created_desc'), $member->username))
                    ->defer();

                if (ee('Request')->post('submit') == 'save_and_new') {
                    ee()->functions->redirect(ee('CP/URL')->make('members/create'));
                } elseif (ee()->input->post('submit') == 'save_and_close') {
                    ee()->functions->redirect(ee('CP/URL')->make('members'));
                } else {
                    ee()->session->set_flashdata('highlight_id', $member->getId());
                    ee()->functions->redirect(ee('CP/URL')->make('members/profile/settings/', ['id' => $member->getId()]));
                }
            } else {
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('member_not_created'))
                    ->addToBody(lang('member_not_created_desc'))
                    ->now();
            }
        }

        $vars = array_merge($vars, [
            'sections' => [],
            'tabs' => [
                'member' => $this->renderMemberTab($vars['errors']),
                'roles' => $this->renderRolesTab($vars['errors']),
            ]
        ]);

        if (! ee('Session')->isWithinAuthTimeout()) {
            $vars['sections']['secure_form_ctrls'] = array(
                array(
                    'title' => 'your_password',
                    'desc' => 'your_password_desc',
                    'group' => 'verify_password',
                    'fields' => array(
                        'verify_password' => array(
                            'type' => 'password',
                            'required' => true,
                            'maxlength' => PASSWORD_MAX_LENGTH
                        )
                    )
                )
            );
        }

        $vars['base_url'] = $this->base_url;
        $vars['ajax_validate'] = true;
        $vars['cp_page_title'] = lang('register_member');
        $vars['buttons'] = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_new',
                'text' => 'save_and_new',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]
        ];

        $vars['cp_breadcrumbs'] = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            '' => lang('create')
        );

        if (ee('Request')->get('modal_form') == 'y') {
            $vars['buttons'] = [[
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]];
            return ee('View')->make('settings/modal-form')->render($vars);
        }

        ee()->cp->render('settings/form', $vars);
    }

    private function renderMemberTab($errors)
    {
        $sections = [[
            [
                'title' => 'username',
                'fields' => [
                    'username' => [
                        'type' => 'text',
                        'required' => true,
                        'maxlength' => USERNAME_MAX_LENGTH
                    ]
                ]
            ],
            [
                'title' => 'mbr_email_address',
                'fields' => [
                    'email' => [
                        'type' => 'text',
                        'required' => true,
                        'maxlength' => 254
                    ]
                ]
            ],
            [
                'title' => 'password',
                'desc' => 'password_desc',
                'fields' => [
                    'password' => [
                        'type' => 'password',
                        'required' => true,
                        'maxlength' => PASSWORD_MAX_LENGTH
                    ]
                ]
            ],
            [
                'title' => 'password_confirm',
                'desc' => 'password_confirm_desc',
                'fields' => [
                    'confirm_password' => [
                        'type' => 'password',
                        'required' => true,
                        'maxlength' => PASSWORD_MAX_LENGTH
                    ]
                ]
            ]
        ]];

        foreach (ee('Model')->make('Member')->getDisplay()->getFields() as $field) {
            if ($field->get('m_field_reg') == 'y' or $field->isRequired()) {
                $sections['custom_fields'][] = [
                    'title' => $field->getLabel(),
                    'desc' => '',
                    'fields' => [
                        $field->getName() => [
                            'type' => 'html',
                            'content' => $field->getForm(),
                            'required' => $field->isRequired(),
                        ]
                    ]
                ];
            }
        }

        $html = '';

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        return $html;
    }

    private function renderRolesTab($errors)
    {
        $allowed_roles = ee('Model')->get('Role')
            ->fields('role_id', 'name')
            ->order('name');
        if (! ee('Permission')->isSuperAdmin()) {
            $allowed_roles->filter('is_locked', 'n');
        }
        $roles = $allowed_roles->all()
            ->getDictionary('role_id', 'name');

        $role_groups = ee('Model')->get('RoleGroup')
            ->fields('group_id', 'name')
            ->order('name')
            ->all()
            ->getDictionary('group_id', 'name');

        $sections = [
            [
                [
                    'title' => 'primary_role',
                    'desc' => 'primary_role_desc',
                    'fields' => [
                        'role_id' => [
                            'type' => 'radio',
                            'required' => true,
                            'choices' => $roles,
                            'value' => ee()->config->item('default_primary_role')
                        ]
                    ]
                ],
            ],
            'additional_roles' => [
                [
                    'title' => 'role_groups',
                    'desc' => 'role_groups_desc',
                    'fields' => [
                        'role_groups' => [
                            'type' => 'checkbox',
                            'choices' => $role_groups,
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('role_groups')),
                                'link_text' => lang('add_new'),
                                'link_href' => ee('CP/URL')->make('members/roles/groups/create')->compile()
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'roles',
                    'desc' => 'roles_desc',
                    'fields' => [
                        'roles' => [
                            'type' => 'checkbox',
                            'choices' => $roles
                        ]
                    ]
                ],
            ]
        ];

        $html = '';

        foreach ($sections as $name => $settings) {
            $html .= ee('View')->make('_shared/form/section')
                ->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
        }

        return $html;
    }

    /**
     * Approve pending members
     *
     * @param int|array $ids The ID(s) of the member(s) being approved
     * @return void
     */
    public function approve($ids)
    {
        if (! ee('Permission')->can('edit_members')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($ids)) {
            $ids = array($ids);
        }

        $members = ee('Model')->get('Member', $ids)
            ->fields('member_id', 'username', 'screen_name', 'email', 'role_id', 'pending_role_id')
            ->filter('role_id', 4)
            ->all();

        if (ee()->config->item('approved_member_notification') == 'y') {
            $template = ee('Model')->get('SpecialtyTemplate')
                ->filter('template_name', 'validated_member_notify')
                ->first();
        }

        $errors = [];
        $approvedCount = 0;
        foreach ($members as $member) {
            $role_id = ee()->config->item('default_primary_role');
            if ($member->pending_role_id != 0) {
                $pendingRole = ee('Model')->get('Role', $member->pending_role_id)->fields('role_id', 'name')->first();
                if (!empty($pendingRole)) {
                    $role_id = $pendingRole->role_id;
                } else {
                    $errors[] = sprintf(lang('cannot_activate_member_role_not_exists'), $member->username, $pendingRole->name);
                    continue;
                }
            }
            $role = ee('Model')->get('Role', $role_id)->first();
            if (empty($role)) {
                $errors[] = sprintf(lang('cannot_activate_member_role_not_exists'), $member->username, $role->name);
                continue;
            }
            if ($role->is_locked == 'y') {
                $errors[] = sprintf(lang('cannot_activate_member_role_is_locked'), $member->username, $role->name);
                continue;
            }
            $member->Roles = new Collection([$role]);
            $member->role_id = $role_id;
            $member->save();
            $approvedCount++;

            if (ee()->config->item('approved_member_notification') == 'y') {
                $this->pendingMemberNotification($template, $member, array('email' => $member->email));
            }
        }

        /* -------------------------------------------
        /* 'cp_members_validate_members' hook.
        /*  - Additional processing when member(s) are validated in the CP
        /*  - Added 1.5.2, 2006-12-28
        */
        ee()->extensions->call('cp_members_validate_members', $ids);
        if (ee()->extensions->end_script === true) {
            return;
        }
        /*
        /* -------------------------------------------*/

        // Update
        ee()->stats->update_member_stats();

        if ($approvedCount == 1) {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('member_approved_success'))
                ->addToBody(sprintf(lang('member_approved_success_desc'), $members->first()->username))
                ->defer();
        } elseif ($approvedCount > 0) {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('members_approved_success'))
                ->addToBody(lang('members_approved_success_desc'))
                ->addToBody($members->pluck('username'))
                ->defer();
        }

        if (count($errors) > 0) {
            $alert = ee('CP/Alert')->makeBanner('members-error')
                ->asWarning()
                ->withTitle(lang('members_approve_error'));
            foreach ($errors as $error) {
                $alert->addToBody($error);
            }
            $alert->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make('members', ['role_filter' => Member::PENDING]));
    }

    protected function listingsPage($primaryRole = null)
    {
        $vars = array();

        $roleId = !empty($primaryRole) ? $primaryRole->getId() : null;

        $base_url = ee('CP/URL')->make('members');

        $members = ee('Model')->get('Member')
            ->with('PrimaryRole', 'Roles');

        $filters = ee('CP/Filter');
        $roleFilter = $this->createRoleFilter($primaryRole);
        if ($roleFilter->value()) {
            if (is_null($primaryRole)) {
                $primaryRole = ee('Model')->get('Role', $roleFilter->value())->first();
            }
            if (!is_null($primaryRole)) {
                $members->filter('member_id', 'IN', $primaryRole->getAllMembersData('member_id'));
            }
        }
        $filters->add('EntryKeyword')
            ->add(
                'SearchIn',
                [
                    'titles' => 'titles',
                    'titles_and_content' => 'titles_and_content',
                ],
                'titles'
            )
            ->withLabel(lang('names_and_email_only'))
            ->add($roleFilter)
            ->add('Date')
            ->withLabel(lang('join_date'));

        $filters->add('MemberManagerColumns', $this->createColumnFilter($primaryRole));

        $filter_values = $filters->values();
        if (! empty($filter_values['filter_by_date'])) {
            if (is_array($filter_values['filter_by_date'])) {
                $members->filter('join_date', '>=', $filter_values['filter_by_date'][0]);
                $members->filter('join_date', '<', $filter_values['filter_by_date'][1]);
            } else {
                $members->filter('join_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
            }
        }

        $search_terms = ee()->input->get_post('filter_by_keyword');
        if ($search_terms) {
            $vars['search_terms'] = htmlentities($search_terms, ENT_QUOTES, 'UTF-8');
            if (is_numeric($search_terms) && strlen($search_terms) < 3) {
                $members->filter('member_id', $search_terms);
            } else {
                if ($filter_values['search_in'] == 'content' || $filter_values['search_in'] == 'titles_and_content') {
                    // setup content fields to use in search
                    $content_fields = [];
                    $custom_fields = ee()->session->getMember()->getAllCustomFields();
                    foreach ($custom_fields as $cf) {
                        $content_fields[] = 'm_field_id_' . $cf->getId();
                    }
                }

                $search_fields = [];
                switch ($filter_values['search_in']) {
                    case 'titles_and_content':
                        $search_fields = array_merge(['username', 'screen_name', 'email', 'member_id'], $content_fields);
                        break;
                    case 'content':
                        $search_fields = $content_fields;
                        break;
                    case 'titles':
                    default:
                        $search_fields = ['username', 'screen_name', 'email', 'member_id'];
                        break;
                }

                $members->search($search_fields, $search_terms);
            }
        }

        $total = $members->count();
        $vars['total_members'] = $total;

        $filters->add('Perpage', $total, 'show_all_members');

        $filter_values = $filters->values();

        $perpage = $filter_values['perpage'];
        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $perpage;

        $base_url->addQueryStringVariables(
            array_filter(
                $filter_values,
                function ($key) {
                    return (!in_array($key, ['columns', 'sort']));
                },
                ARRAY_FILTER_USE_KEY
            )
        );

        // Get order by and sort preferences for our initial state
        $sort_col = (ee()->config->item('memberlist_order_by')) ?: 'member_id';
        $sort_dir = (ee()->config->item('memberlist_sort_order')) ?: 'asc';
        $table = ee('CP/Table', array(
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
            'class' => 'tbl-fixed'
        ));

        //which columns should we show
        $columns = [];
        $filter_values['columns'][] = 'manage';
        $filter_values['columns'][] = 'checkbox';
        foreach ($filter_values['columns'] as $column) {
            $columns[$column] = ColumnFactory::getColumn($column);
        }
        $columns = array_filter($columns);

        foreach ($columns as $column) {
            if (!empty($column)) {
                if (!empty($column->getEntryManagerColumnModels())) {
                    foreach ($column->getEntryManagerColumnModels() as $with) {
                        if (!empty($with)) {
                            $members->with($with);
                        }
                    }
                }
            }
        }

        $column_renderer = new ColumnRenderer($columns);
        $table_columns = $column_renderer->getTableColumnsConfig();
        $table->setColumns($table_columns);

        if (!empty($primaryRole)) {
            $table->setNoResultsText(sprintf(lang('no_role_members_found'), $primaryRole->name));
        } else {
            $table->setNoResultsText('no_members_found');
        }

        $vars['bulk_options'] = [];
        if (!empty($primaryRole) && $primaryRole->role_id == Member::PENDING) {
            if (ee('Permission')->can('edit_members')) {
                $vars['bulk_options'][] = [
                    'value' => "approve",
                    'text' => lang('approve')
                ];
            }
            if (ee('Permission')->can('delete_members')) {
                $vars['bulk_options'][] = [
                    'value' => "decline",
                    'text' => lang('decline'),
                    'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-decline"'
                ];
            }
        } else {
            if (ee('Permission')->can('delete_members')) {
                $vars['bulk_options'][] = [
                    'value' => "remove",
                    'text' => lang('delete'),
                    'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
                ];
            }
        }

        foreach ($table_columns as $table_column) {
            if ($table_column['label'] == $table->sort_col) {
                $sort_col = $table_column['name'];

                break;
            }
        }

        // Fix for an issue where users may have 'total_posts' saved
        // in their site settings for sorting members; but the actual
        // column should be total_forum_posts, so we need to correct
        // it until member preferences can be saved again with the
        // right value
        $sort_field = ($sort_col == 'total_posts') ? 'total_forum_posts' : $columns[$sort_col]->getEntryManagerColumnSortField();
        $preselectedId = ee()->session->flashdata('highlight_id');

        if ($preselectedId) {
            $members = $members->order('FIELD( member_id, ' . $preselectedId . ' )', 'DESC', false);
        }

        if (! ($table->sort_dir == $sort_dir && $table->sort_col == $sort_col)) {
            $base_url->addQueryStringVariables(
                array(
                    'sort_dir' => $table->sort_dir,
                    'sort_col' => $table->sort_col
                )
            );
        }

        $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($perpage)
            ->currentPage($page)
            ->render($base_url);

        $members = $members->order($sort_field, $table->sort_dir)
            ->limit($perpage)
            ->offset($offset)
            ->all();

        $data = array();

        foreach ($members as $member) {
            $attrs = [
                'member_id' => $member->member_id,
                'title' => $member->screen_name,
            ];


            if ($preselectedId && $member->member_id == $preselectedId) {
                $attrs['class'] .= ' selected';
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $column_renderer->getRenderedTableRowForEntry($member)
            );
        }

        $table->setData($data);

        $vars['table'] = $table->viewData($base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        $vars['filters'] = $filters->renderEntryFilters($base_url);
        $vars['filters_search'] = $filters->renderSearch($base_url);
        $vars['search_value'] = htmlentities(ee()->input->get_post('filter_by_keyword'), ENT_QUOTES, 'UTF-8');
        $vars['role_id'] = $roleId;

        ee()->javascript->set_global([
            'viewManager.saveDefaultUrl' => ee('CP/URL')->make('members/views/save-default', ['role_id' => $roleId])->compile()
        ]);

        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
                'cp/publish/entry-list',
            ),
        ));
        return $vars;
    }

    /**
     * Creates role filter
     */
    private function createRoleFilter($roleId = null)
    {
        $roles = ee('Model')->get('Role')
            ->order('role_id', 'asc')
            ->all(true)
            ->getDictionary('role_id', 'name');

        $filter = ee('CP/Filter')->make('role_filter', lang('role'), $roles);
        $filter->useListFilter();

        return $filter;
    }

    /**
     * Creates a column filter
     */
    private function createColumnFilter($primaryRole = null)
    {
        $column_choices = [];

        $columns = ColumnFactory::getAvailableColumns($primaryRole);

        foreach ($columns as $column) {
            $identifier = $column->getTableColumnIdentifier();

            // This column is mandatory, not optional
            if (in_array($identifier, ['manage', 'checkbox'])) {
                continue;
            }

            $column_choices[$identifier] = strip_tags(lang($column->getTableColumnLabel()));
        }

        return $column_choices;
    }
}
