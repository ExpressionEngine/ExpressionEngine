<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members;

use CP_Controller;
use ExpressionEngine\Library\CP;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Member\Member;

/**
 * Members Controller
 */
class Members extends CP_Controller
{
    protected $base_url;

    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_members')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('members');

        $this->base_url = ee('CP/URL')->make('members');
        $this->stdHeader();
    }

    protected function generateSidebar($active = null)
    {
        $sidebar = ee('CP/Sidebar')->make();

        $sidebar->addHeader(lang('members'));

        $all = $sidebar->addItem(lang('all_members'), ee('CP/URL')->make('members')->compile());

        if ($active == 'all_members') {
            $all->isActive();
        }

        if (ee('Permission')->can('edit_members')) {
            $pending = $sidebar->addItem(lang('pending_activation'), ee('CP/URL', 'members/pending')->compile());

            if ($active == 'pending') {
                $pending->isActive();
            }

            $banned = $sidebar->addItem(lang('banned'), ee('CP/URL', 'members/banned')->compile());

            if ($active == 'banned') {
                $banned->isActive();
            }
        }

        if (ee('Permission')->can('admin_roles')) {
            $sidebar->addHeader(lang('member_settings'));

            $member_roles = $sidebar->addItem(lang('member_roles'), ee('CP/URL')->make('members/roles'))->withIcon('user-tag');
            $custom_member_fields = $sidebar->addItem(lang('custom_member_fields'), ee('CP/URL')->make('members/fields'))->withIcon('bars');

            if ($active == 'roles') {
                $member_roles->isActive();
            }

            if ($active == 'fields') {
                $custom_member_fields->isActive();
            }
        }

        if (ee('Permission')->can('ban_users')) {
            $ban_settings = $sidebar->addItem(lang('manage_bans'), ee('CP/URL')->make('members/ban-settings'))->withIcon('ban');

            if ($active == 'ban-settings') {
                $ban_settings->isActive();
            }
        }
    }

    public function index()
    {
        if (ee('Request')->post('bulk_action') == 'remove') {
            $this->delete();
            ee()->functions->redirect($this->base_url);
        }

        $members = ee('Model')->get('Member')->with('PrimaryRole', 'Roles');

        $filters = $this->makeAndApplyFilters($members, true);
        $vars['filters'] = $filters->render($this->base_url);

        $filter_values = $filters->values();
        $this->base_url->addQueryStringVariables($filter_values);

        $page = ((int) ee('Request')->get('page')) ?: 1;
        $offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

        $total_members = $members->count();

        $members->limit($filter_values['perpage'])
            ->offset($offset);

        $this->generateSidebar('all_members');

        $table = $this->buildTableFromMemberQuery($members);

        $vars['table'] = $table->viewData($this->base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        if (! empty($vars['table']['data'])) {
            $vars['pagination'] = ee('CP/Pagination', $total_members)
                ->perPage($filter_values['perpage'])
                ->currentPage($page)
                ->render($this->base_url);
        }

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

        $vars['can_delete_members'] = ee('Permission')->can('delete_members');

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('all_members');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('members')
        );

        ee()->cp->render('members/view_members', $vars);
    }

    public function pending()
    {
        $this->base_url = ee('CP/URL')->make('members/pending');

        $action = ee('Request')->post('bulk_action');

        if ($action) {
            $ids = ee('Request')->post('selection');
            switch ($action) {
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

        $this->generateSidebar('pending');

        $members = ee('Model')->get('Member')
            ->with('PrimaryRole', 'Roles')
            ->filter('role_id', 4);

        $vars = array(
            'can_edit' => ee('Permission')->can('edit_members'),
            'can_delete' => ee('Permission')->can('delete_members'),
            'resend_available' => (ee()->config->item('req_mbr_activation') == 'email')
        );

        $filters = $this->makeAndApplyFilters($members, false);
        $vars['filters'] = $filters->render($this->base_url);

        $filter_values = $filters->values();

        $page = ((int) ee('Request')->get('page')) ?: 1;
        $offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

        $total_members = $members->count();

        $members->limit($filter_values['perpage'])
            ->offset($offset);

        $table = $this->buildTableFromMemberQuery($members);

        $vars['table'] = $table->viewData($this->base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        if (! empty($vars['table']['data'])) {
            $vars['pagination'] = ee('CP/Pagination', $total_members)
                ->perPage($filter_values['perpage'])
                ->currentPage($page)
                ->render($this->base_url);
        }

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

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('pending_members');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            '' => lang('pending')
        );

        ee()->cp->render('members/pending', $vars);
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
    }

    /**
     * Resend activation emails for pending members
     *
     * @param array $ids The ID(s) of the member(s) being approved
     * @return void
     */
    private function resend(array $ids)
    {
        if (! ee('Permission')->can('edit_members') or
            ee()->config->item('req_mbr_activation') !== 'email') {
            show_error(lang('unauthorized_access'), 403);
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
        if (! ee('Permission')->can('ban_users')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->load->library('form_validation');

        $this->base_url = ee('CP/URL', 'members/banned');

        if (ee('Request')->post('bulk_action') == 'remove') {
            $this->delete();
            ee()->functions->redirect($this->base_url);
        }

        $this->generateSidebar('banned');

        $members = ee('Model')->get('Member')
            ->with('PrimaryRole', 'Roles')
            ->filter('role_id', 2);

        $filters = $this->makeAndApplyFilters($members, false);
        $vars['filters'] = $filters->render($this->base_url);

        $filter_values = $filters->values();

        $page = ((int) ee('Request')->get('page')) ?: 1;
        $offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

        $total_members = $members->count();

        $members->limit($filter_values['perpage'])
            ->offset($offset);

        $table = $this->buildTableFromMemberQuery($members);

        $vars['table'] = $table->viewData($this->base_url);
        $vars['form_url'] = $vars['table']['base_url'];

        if (! empty($vars['table']['data'])) {
            $vars['pagination'] = ee('CP/Pagination', $total_members)
                ->perPage($filter_values['perpage'])
                ->currentPage($page)
                ->render($this->base_url);
        }

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

        $vars['can_delete_members'] = ee('Permission')->can('delete_members');

        $values = [
            'banned_ips' => '',
            'banned_emails' => '',
            'banned_usernames' => '',
            'banned_screen_names' => '',
        ];

        foreach (array_keys($values) as $item) {
            $value = ee()->config->item($item);

            if ($value != '') {
                foreach (explode('|', $value) as $line) {
                    $values[$item] .= $line . NL;
                }
            }
        }

        $ban_action = ee()->config->item('ban_action');

        $vars['form'] = array(
            'ajax_validate' => true,
            'base_url' => $this->base_url,
            'cp_page_title' => lang('manage_bans'),
            'save_btn_text' => sprintf(lang('btn_save'), lang('settings')),
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(
                array(
                    array(
                        'title' => 'ip_address_banning',
                        'desc' => 'ip_banning_instructions',
                        'fields' => array(
                            'banned_ips' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_ips']
                            )
                        )
                    ),
                    array(
                        'title' => 'email_address_banning',
                        'desc' => 'email_banning_instructions',
                        'fields' => array(
                            'banned_emails' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_emails']
                            )
                        )
                    ),
                    array(
                        'title' => 'username_banning',
                        'desc' => 'username_banning_instructions',
                        'fields' => array(
                            'banned_usernames' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_usernames']
                            )
                        )
                    ),
                    array(
                        'title' => 'screen_name_banning',
                        'desc' => 'screen_name_banning_instructions',
                        'fields' => array(
                            'banned_screen_names' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_screen_names']
                            )
                        )
                    ),
                    array(
                        'title' => 'ban_options',
                        'desc' => 'ban_options_desc',
                        'fields' => array(
                            'ban_action_pt1' => array(
                                'type' => 'radio',
                                'name' => 'ban_action',
                                'choices' => array(
                                    'restrict' => lang('restrict_to_viewing'),
                                    'message' => lang('show_this_message'),
                                ),
                                'value' => $ban_action
                            ),
                            'ban_message' => array(
                                'type' => 'textarea',
                                'value' => ee()->config->item('ban_message')
                            ),
                            'ban_action_pt2' => array(
                                'type' => 'radio',
                                'name' => 'ban_action',
                                'choices' => array(
                                    'bounce' => lang('send_to_site'),
                                ),
                                'value' => $ban_action
                            ),
                            'ban_destination' => array(
                                'type' => 'text',
                                'value' => ee()->config->item('ban_destination')
                            ),
                        )
                    )
                )
            )
        );

        // @TODO: Stop using form_validation
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'banned_usernames',
                'label' => 'lang:banned_usernames',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_screen_names',
                'label' => 'lang:banned_screen_names',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_emails',
                'label' => 'lang:banned_emails',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_ips',
                'label' => 'lang:banned_ips',
                'rules' => 'valid_xss_check'
            )
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $prefs = [
                'ban_action' => ee('Request')->post('ban_action'),
                'ban_message' => ee('Request')->post('ban_message'),
                'ban_destination' => ee('Request')->post('ban_destination'),
            ];

            foreach (array_keys($values) as $item) {
                $value = ee('Request')->post($item);
                $value = implode('|', explode(NL, $value));
                $prefs[$item] = $value;
            }

            ee()->config->update_site_prefs($prefs);

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('ban_settings_updated'))
                ->defer();

            ee()->functions->redirect($this->base_url);
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('settings_save_error_desc'))
                ->now();
        }

        ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('banned_members');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            '' => lang('banned')
        );

        ee()->cp->render('members/banned', $vars);
    }

    public function banSettings()
    {
        if (!ee('Permission')->can('ban_users')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->load->library('form_validation');

        $this->generateSidebar('ban-settings');
        $this->stdHeader();
        $this->base_url = ee('CP/URL', 'members/ban-settings');

        $values = [
            'banned_ips' => '',
            'banned_emails' => '',
            'banned_usernames' => '',
            'banned_screen_names' => '',
        ];

        foreach (array_keys($values) as $item) {
            $value = ee()->config->item($item);

            if ($value != '') {
                foreach (explode('|', $value) as $line) {
                    $values[$item] .= $line . NL;
                }
            }
        }

        $ban_action = ee()->config->item('ban_action');

        $vars['form'] = array(
            'ajax_validate' => true,
            'base_url' => $this->base_url,
            'cp_page_title' => lang('manage_bans'),
            'save_btn_text' => sprintf(lang('btn_save'), lang('settings')),
            'save_btn_text_working' => 'btn_saving',
            'sections' => array(
                array(
                    array(
                        'title' => 'ip_address_banning',
                        'desc' => 'ip_banning_instructions',
                        'fields' => array(
                            'banned_ips' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_ips']
                            )
                        )
                    ),
                    array(
                        'title' => 'email_address_banning',
                        'desc' => 'email_banning_instructions',
                        'fields' => array(
                            'banned_emails' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_emails']
                            )
                        )
                    ),
                    array(
                        'title' => 'username_banning',
                        'desc' => 'username_banning_instructions',
                        'fields' => array(
                            'banned_usernames' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_usernames']
                            )
                        )
                    ),
                    array(
                        'title' => 'screen_name_banning',
                        'desc' => 'screen_name_banning_instructions',
                        'fields' => array(
                            'banned_screen_names' => array(
                                'type' => 'textarea',
                                'value' => $values['banned_screen_names']
                            )
                        )
                    ),
                    array(
                        'title' => 'ban_options',
                        'desc' => 'ban_options_desc',
                        'fields' => array(
                            'ban_action_pt1' => array(
                                'type' => 'radio',
                                'name' => 'ban_action',
                                'choices' => array(
                                    'restrict' => lang('restrict_to_viewing'),
                                    'message' => lang('show_this_message'),
                                ),
                                'value' => $ban_action
                            ),
                            'ban_message' => array(
                                'type' => 'textarea',
                                'value' => ee()->config->item('ban_message')
                            ),
                            'ban_action_pt2' => array(
                                'type' => 'radio',
                                'name' => 'ban_action',
                                'choices' => array(
                                    'bounce' => lang('send_to_site'),
                                ),
                                'value' => $ban_action
                            ),
                            'ban_destination' => array(
                                'type' => 'text',
                                'value' => ee()->config->item('ban_destination')
                            ),
                        )
                    )
                )
            )
        );

        // @TODO: Stop using form_validation
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'banned_usernames',
                'label' => 'lang:banned_usernames',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_screen_names',
                'label' => 'lang:banned_screen_names',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_emails',
                'label' => 'lang:banned_emails',
                'rules' => 'valid_xss_check'
            ),
            array(
                'field' => 'banned_ips',
                'label' => 'lang:banned_ips',
                'rules' => 'valid_xss_check'
            )
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $prefs = [
                'ban_action' => ee('Request')->post('ban_action'),
                'ban_message' => ee('Request')->post('ban_message'),
                'ban_destination' => ee('Request')->post('ban_destination'),
            ];

            foreach (array_keys($values) as $item) {
                $value = ee('Request')->post($item);
                $value = implode('|', explode(NL, $value));
                $prefs[$item] = $value;
            }

            ee()->config->update_site_prefs($prefs);

            ee('CP/Alert')->makeInline('shared-form')
                ->asSuccess()
                ->withTitle(lang('ban_settings_updated'))
                ->defer();

            ee()->functions->redirect($this->base_url);
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('settings_save_error_desc'))
                ->now();
        }

        ee()->view->cp_page_title = ee()->view->cp_page_title ?: lang('manage_bans');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            '' => lang('manage_bans')
        );

        ee()->cp->render('members/ban_settings', $vars);
    }

    private function initializeTable()
    {
        $checkboxes = ee('Permission')->can('delete_members');

        // Get order by and sort preferences for our initial state
        $order_by = (ee()->config->item('memberlist_order_by')) ?: 'member_id';
        $sort = (ee()->config->item('memberlist_sort_order')) ?: 'asc';

        // Fix for an issue where users may have 'total_posts' saved
        // in their site settings for sorting members; but the actual
        // column should be total_forum_posts, so we need to correct
        // it until member preferences can be saved again with the
        // right value
        if ($order_by == 'total_posts') {
            $order_by = 'total_forum_posts';
        }

        $sort_col = ee('Request')->get('sort_col') ?: $order_by;
        $sort_dir = ee('Request')->get('sort_dir') ?: $sort;

        $table = ee('CP/Table', array(
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
            'limit' => ee()->config->item('memberlist_row_limit'),
            // 'search' => ee('Request')->get_post('filter_by_keyword'),
        ));

        $table->setNoResultsText('no_members_found');

        $columns = array(
            'member_id' => array(
                'type' => Table::COL_ID
            ),
            'username' => array(
                'encode' => false
            ),
            'dates' => array(
                'encode' => false
            ),
            'roles' => array(
                'encode' => false
            )
        );

        if ($checkboxes) {
            $columns[] = array(
                'type' => Table::COL_CHECKBOX
            );
        }

        $table->setColumns($columns);

        return $table;
    }

    private function buildTableFromMemberQuery(Builder $members, $checkboxes = null)
    {
        $primary_icon = ' <sup class="icon--primary" title="' . lang('primary_role') . '"></sup>';
        
        $table = $this->initializeTable();

        $sort_map = array(
            'member_id' => 'member_id',
            'username' => 'username',
            'dates' => 'join_date',
            'roles' => 'role_id'
        );

        $members = $members->order($sort_map[$table->sort_col], $table->config['sort_dir'])
            ->all();

        $data = array();

        $member_id = ee()->session->flashdata('highlight_id');

        foreach ($members as $member) {
            if (!ee('Permission')->isSuperAdmin()) {
                $can_operate_member = (bool) ($member->PrimaryRole->is_locked != 'y');
            } else {
                $can_operate_member = true;
            }

            $edit_link = ee('CP/URL')->make('members/profile/', array('id' => $member->member_id));

            $attrs = array();

            switch ($member->PrimaryRole->getId()) {
                case Member::BANNED:
                    $group = "<span class='st-banned'>" . lang('banned') . "</span>";
                    $attrs['class'] = 'banned';

                    break;
                case Member::PENDING:
                    $group = "<span class='st-pending'>" . lang('pending') . "</span>";
                    $attrs['class'] = 'pending';

                    if (ee('Permission')->can('edit_members')) {
                        $group .= "<a class=\"success-link icon-right button button--small button--default\" href=\"" . ee('CP/URL')->make('members/approve/' . $member->member_id) . "\" title=\"" . lang('approve') . "\"><i class=\"fas fa-check\"><span class=\"hidden\">" . lang('approve') . "</span></i></a>";
                    }

                    break;
                default:
                    $group = $member->PrimaryRole->name . $primary_icon;
            }

            foreach ($member->getAllRoles() as $role) {
                if ($role->getId() != 0 && $role->getId() != $member->role_id) {
                    $group .= ', ' . $role->name;
                }
            }

            $email = "<a class=\"text-muted\" href='" . ee('CP/URL')->make('utilities/communicate/member/' . $member->member_id) . "'>" . $member->email . "</a>";

            if (ee('Permission')->can('edit_members') && $can_operate_member) {
                $username_display = "<a href = '" . $edit_link . "'>" . $member->username . "</a>";
            } else {
                $username_display = $member->username;
            }

            $username_display .= '<br><span class="meta-info">' . $email . '</span>';

            $avatar_url = ($member->avatar_filename) ? ee()->config->slash_item('avatar_url') . $member->avatar_filename : (URL_THEMES . 'asset/img/default-avatar.png');

            $username_display = "
            <div class=\"d-flex align-items-center\">
            <img src=\"$avatar_url\" alt=\"" . $member->username . "\" class=\"avatar-icon add-mrg-right\">
            <div>$username_display</div>
            </div>
            ";

            $last_visit = ($member->last_visit) ? ee()->localize->human_time($member->last_visit) : '--';

            $column = array(
                $member->member_id,
                $username_display,
                '<span class="meta-info">
                    <b>' . lang('joined') . '</b>: ' . ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $member->join_date) . '<br>
                    <b>' . lang('last_visit') . '</b>: ' . $last_visit . '
                </span>',
                $group
            );

            // add the checkbox if they can delete members
            if (ee('Permission')->can('delete_members')) {
                $column[] = array(
                    'name' => 'selection[]',
                    'value' => $member->member_id,
                    'data' => array(
                        'confirm' => lang('member') . ': <b>' . htmlentities($member->username, ENT_QUOTES, 'UTF-8') . '</b>'
                    ),
                    'disabled' => !$can_operate_member || ($member->member_id == ee()->session->userdata('member_id'))
                );
            }

            if ($member_id && $member->member_id == $member_id) {
                $attrs = array('class' => 'selected');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $column
            );
        }

        $table->setData($data);

        return $table;
    }

    protected function makeAndApplyFilters($members, $roles = false)
    {
        $filters = ee('CP/Filter');

        if ($roles) {
            $roles = ee('Model')->get('Role')
                ->order('name', 'asc')
                ->all()
                ->getDictionary('role_id', 'name');

            $role_filter = $filters->make('role_filter', 'role_filter', $roles);
            $role_filter->setPlaceholder(lang('all'));
            $role_filter->disableCustomValue();

            $filters->add($role_filter);
        }

        $filters->add('Keyword');

        $filter_values = $filters->values();

        foreach ($filter_values as $key => $value) {
            if ($value) {
                if ($key == 'filter_by_keyword') {
                    $members->search(['screen_name', 'username', 'email', 'member_id'], $value);
                } elseif ($key == 'role_filter') {
                    $role = ee('Model')->get('Role', $value)->first();

                    if ($role) {
                        $members->filter('member_id', 'IN', $role->getAllMembers()->pluck('member_id'));
                    }
                } else {
                    $members->filter($key, $value);
                }
            }
        }

        $filters->add('Perpage', $members->count(), 'show_all_members');

        return $filters;
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
        $search_term = ee('Request')->get('search') ?: '';
        $group_ids = $group_ids ?: explode('|', ee('Request')->get('group_ids'));
        $selected = $selected ?: explode('|', ee('Request')->get('selected'));

        $members = ee('Model')->get('Member')
            ->fields('screen_name', 'username')
            ->search(
                ['screen_name', 'username', 'email', 'member_id'],
                $search_term
            )
            ->filter('role_id', 'IN', $group_ids)
            ->filter('member_id', 'NOT IN', $selected)
            ->order('screen_name')
            ->limit(100)
            ->all();

        $heirs = [];
        foreach ($members as $heir) {
            $name = ($heir->screen_name != '') ? 'screen_name' : 'username';
            $heirs[$heir->getId()] = $heir->$name;
        }

        return ee('View/Helpers')->normalizedChoices($heirs);
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

        if (! ee('Permission')->can('delete_members') ||
            ! $member) {
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

        if (! ee('Permission')->can('delete_members') ||
            ! $member_ids) {
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

        $this->base_url = ee('CP/URL')->make('members/create');

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

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('register_member');
        ee()->view->buttons = [
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

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            '' => lang('create')
        );

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
                        'maxlength' => USERNAME_MAX_LENGTH
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
            ->fields('member_id', 'username', 'screen_name', 'email', 'role_id')
            ->filter('role_id', 4)
            ->all();

        if (ee()->config->item('approved_member_notification') == 'y') {
            $template = ee('Model')->get('SpecialtyTemplate')
                ->filter('template_name', 'validated_member_notify')
                ->first();

            foreach ($members as $member) {
                $this->pendingMemberNotification($template, $member, array('email' => $member->email));
            }
        }

        $members->role_id = ee()->config->item('default_primary_role');
        $members->save();

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

        if ($members->count() == 1) {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('member_approved_success'))
                ->addToBody(sprintf(lang('member_approved_success_desc'), $members->first()->username))
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('view-members')
                ->asSuccess()
                ->withTitle(lang('members_approved_success'))
                ->addToBody(lang('members_approved_success_desc'))
                ->addToBody($members->pluck('username'))
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL', 'members/pending'));
    }

    /**
     * Set the header for the members section
     * @param String $form_url Form URL
     * @param String $search_button_value The text for the search button
     */
    protected function stdHeader()
    {
        $header = [
            'title' => lang('member_manager'),
            'toolbar_items' => [
                'settings' => [
                    'href' => ee('CP/URL')->make('settings/members'),
                    'title' => lang('member_settings')
                ],
            ],
            'action_button' => ee('Permission')->can('create_members') ? [
                'text' => lang('new_member'),
                'href' => ee('CP/URL')->make('members/create')
            ] : null
        ];

        if (!ee('Permission')->can('access_settings')) {
            unset($header['toolbar_items']);
        }

        ee()->view->header = $header;
    }
}
