<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Model\Email\EmailCache;

/**
 * Communicate Controller
 */
class Communicate extends Utilities
{
    private $attachments = array();
    private $member;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_comm')) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * Index
     *
     * @param	obj	$email	An EmailCache object for use in re-populating the form (see: resend())
     * @return string
     */
    public function index(EmailCache $email = null)
    {
        $default = array(
            'from' => ee()->session->userdata('email'),
            'recipient' => '',
            'cc' => '',
            'bcc' => '',
            'subject' => '',
            'message' => '',
            'plaintext_alt' => '',
            'mailtype' => ee()->config->item('mail_format'),
            'wordwrap' => ee()->config->item('word_wrap')
        );

        $vars['mailtype_options'] = array(
            'text' => lang('plain_text'),
            'markdown' => lang('markdown'),
            'html' => lang('html')
        );

        $roles = array();

        if (! is_null($email)) {
            $default['from'] = $email->from_email;
            $default['recipient'] = $email->recipient;
            $default['cc'] = $email->cc;
            $default['bcc'] = $email->bcc;
            $default['subject'] = $email->subject;
            $default['message'] = $email->message;
            $default['plaintext_alt'] = $email->plaintext_alt;
            $default['mailtype'] = $email->mailtype;
            $default['wordwrap'] = $email->wordwrap;

            if (! isset($this->member)) {
                $roles = $email->Roles->pluck('role_id');
            }
        }

        // Set up member role emailing options
        if (ee('Permission')->can('email_roles')) {
            $roles = ee('Model')->get('Role')->all();

            $member_roles = [];
            $disabled_roles = [];
            foreach ($roles as $role) {
                $member_roles[$role->role_id] = $role->name . '&nbsp<span class="faded">(' . $role->total_members . ')</span>';

                if ($role->total_members == 0) {
                    $disabled_roles[] = $role->role_id;
                }
            }
        }

        $vars['cp_page_title'] = lang('send_email');

        if ($default['mailtype'] != 'html') {
            ee()->javascript->output('$("textarea[name=\'plaintext_alt\']").parents("fieldset").eq(0).hide();');
        }

        ee()->javascript->change("select[name=\'mailtype\']", '
			if ($("select[name=\'mailtype\']").val() == "html")
			{
				$("textarea[name=\'plaintext_alt\']").parents("fieldset").eq(0).slideDown();
			}
			else
			{
				$("textarea[name=\'plaintext_alt\']").parents("fieldset").eq(0).slideUp();
			}
		');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'email_subject',
                    'fields' => array(
                        'subject' => array(
                            'type' => 'text',
                            'required' => true,
                            'value' => $default['subject']
                        )
                    )
                ),
                array(
                    'title' => 'email_body',
                    'fields' => array(
                        'message' => array(
                            'type' => 'html',
                            'content' => ee('View')->make('utilities/communicate/body-field')
                                ->render($default + $vars),
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'plaintext_body',
                    'desc' => 'plaintext_alt',
                    'fields' => array(
                        'plaintext_alt' => array(
                            'type' => 'textarea',
                            'value' => $default['plaintext_alt'],
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'your_email',
                    'fields' => array(
                        'from' => array(
                            'type' => 'text',
                            'value' => $default['from'],
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'attachment',
                    'desc' => 'attachment_desc',
                    'fields' => array(
                        'attachment' => array(
                            'type' => 'file'
                        )
                    )
                )
            ),
            'recipient_options' => array(
                array(
                    'title' => 'primary_recipients',
                    'desc' => 'primary_recipients_desc',
                    'fields' => array(
                        'recipient' => array(
                            'type' => 'text',
                            'value' => $default['recipient']
                        )
                    )
                ),
                array(
                    'title' => 'cc_recipients',
                    'desc' => 'cc_recipients_desc',
                    'fields' => array(
                        'cc' => array(
                            'type' => 'text',
                            'value' => $default['cc']
                        )
                    )
                ),
                array(
                    'title' => 'bcc_recipients',
                    'desc' => 'bcc_recipients_desc',
                    'fields' => array(
                        'bcc' => array(
                            'type' => 'text',
                            'value' => $default['bcc']
                        )
                    )
                )
            )
        );

        if (ee('Permission')->can('email_roles')) {
            $vars['sections']['recipient_options'][] = ee('CP/Alert')->makeInline('roles-warn')
                    ->asWarning()
                    ->addToBody(lang('roles_send_warning'))
                    ->cannotClose()
                    ->render();
            if (bool_config_item('ignore_member_stats')) {
                ee()->lang->load('members');
                $vars['sections']['recipient_options'][] = ee('CP/Alert')->makeInline('roles-count-warn')
                    ->asWarning()
                    ->addToBody(lang('roles_counter_warning'))
                    ->cannotClose()
                    ->render();
            }
            $vars['sections']['recipient_options'][] = array(
                'title' => 'add_member_roles',
                'desc' => 'add_member_roles_desc',
                'fields' => array(
                    'member_roles' => array(
                        'type' => 'checkbox',
                        'choices' => $member_roles,
                        'disabled_choices' => $disabled_roles,
                    )
                )
            );
        }

        $vars['base_url'] = ee('CP/URL')->make('utilities/communicate/send');
        $vars['save_btn_text'] = 'btn_send_email';
        $vars['save_btn_text_working'] = 'btn_send_email_working';
        $vars['has_file_input'] = true;

        ee()->view->cp_breadcrumbs = array(
            '' => lang('send_email')
        );

        return ee()->cp->render('settings/form', $vars);
    }

    /**
     * Prepopulate form to send to specific member
     *
     * @param int $id
     * @access public
     * @return void
     */
    public function member($id)
    {
        $member = ee('Model')->get('Member', $id)->first();
        $this->member = $member;

        if (empty($member)) {
            show_404();
        }

        $cache_data = array(
            'recipient' => $member->email,
            'from_email' => ee()->session->userdata('email')
        );

        $email = ee('Model')->make('EmailCache', $cache_data);
        $email->Roles = null;
        $this->index($email);
    }

    /**
     * Send Email
     */
    public function send()
    {
        ee()->load->library('email');

        // Fetch $_POST data
        // We'll turn the $_POST data into variables for simplicity

        $roles = array();

        $form_fields = array(
            'subject',
            'message',
            'plaintext_alt',
            'mailtype',
            'wordwrap',
            'from',
            'attachment',
            'recipient',
            'cc',
            'bcc'
        );

        $wordwrap = 'n';

        foreach ($_POST as $key => $val) {
            if ($key == 'member_roles') {
                // filter empty inputs, like a hidden no-value input from React
                $roles = array_filter(ee()->input->post($key));
            } elseif (in_array($key, $form_fields)) {
                $$key = ee()->input->post($key);
            }
        }

        //  Verify privileges
        if (count($roles) > 0 && ! ee('Permission')->can('email_roles')) {
            show_error(lang('not_allowed_to_email_member_groups'));
        }

        // Set to allow a check for at least one recipient
        $_POST['total_gl_recipients'] = count($roles);

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules('subject', 'lang:subject', 'required|valid_xss_check');
        ee()->form_validation->set_rules('message', 'lang:message', 'required');
        ee()->form_validation->set_rules('from', 'lang:from', 'required|valid_email');
        ee()->form_validation->set_rules('cc', 'lang:cc', 'valid_emails');
        ee()->form_validation->set_rules('bcc', 'lang:bcc', 'valid_emails');
        ee()->form_validation->set_rules('recipient', 'lang:recipient', 'valid_emails|callback__check_for_recipients');
        ee()->form_validation->set_rules('attachment', 'lang:attachment', 'callback__attachment_handler');

        if (ee()->form_validation->run() === false) {
            ee()->view->set_message('issue', lang('communicate_error'), lang('communicate_error_desc'));

            return $this->index();
        }

        $name = ee()->session->userdata('screen_name');

        ee()->view->cp_page_title = lang('email_success');
        $debug_msg = '';

        switch ($mailtype) {
            case 'text':
                $text_fmt = 'none';
                $plaintext_alt = '';

                break;

            case 'markdown':
                $text_fmt = 'markdown';
                $mailtype = 'html';
                $plaintext_alt = $message;

                break;

            case 'html':
                // If we strip tags and it matches the message, then there was
                // not any HTML in it and we'll format for them.
                if ($message == strip_tags($message)) {
                    $text_fmt = 'xhtml';
                } else {
                    $text_fmt = 'none';
                }

                break;
        }

        // Assign data for caching
        $cache_data = array(
            'cache_date' => ee()->localize->now,
            'total_sent' => 0,
            'from_name' => $name,
            'from_email' => $from,
            'recipient' => $recipient,
            'cc' => $cc,
            'bcc' => $bcc,
            'recipient_array' => array(),
            'subject' => $subject,
            'message' => $message,
            'mailtype' => $mailtype,
            'wordwrap' => $wordwrap,
            'text_fmt' => $text_fmt,
            'plaintext_alt' => $plaintext_alt,
            'attachments' => $this->attachments,
        );

        $email = ee('Model')->make('EmailCache', $cache_data);
        $email->save();

        //  Send a single email
        if (count($roles) == 0) {
            $debug_msg = $this->deliverOneEmail($email, $recipient);

            ee()->view->set_message('success', lang('email_sent_message'), $debug_msg, true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/communicate'));
        }

        // Get member role emails
        $member_roles = ee('Model')->get('Role', $roles)
            ->with('Members')
            ->all();

        $email_addresses = array();
        foreach ($member_roles as $role) {
            $email_addresses = array_merge($email_addresses, $role->getAllMembersData('email'));
        }

        if (empty($email_addresses) and $recipient == '') {
            show_error(lang('no_email_matching_criteria'));
        }

        /** ----------------------------------------
        /**  Do we have any CCs or BCCs?
        /** ----------------------------------------*/

        //  If so, we'll send those separately first

        $total_sent = 0;

        if ($cc != '' or $bcc != '') {
            $to = ($recipient == '') ? ee()->session->userdata['email'] : $recipient;
            $debug_msg = $this->deliverOneEmail($email, $to, empty($email_addresses));

            $total_sent = $email->total_sent;
        } else {
            // No CC/BCCs? Convert recipients to an array so we can include them in the email sending cycle

            if ($recipient != '') {
                foreach (explode(',', $recipient) as $address) {
                    $address = trim($address);

                    if (! empty($address)) {
                        $email_addresses[] = $address;
                    }
                }
            }
        }

        //  Store email cache
        $email->recipient_array = $email_addresses;
        $email->Roles = ee('Model')->get('Role', $roles)->all();
        $email->save();
        $id = $email->cache_id;

        // Is Batch Mode set?

        $batch_mode = bool_config_item('email_batchmode');
        $batch_size = (int) ee()->config->item('email_batch_size');

        if (count($email_addresses) <= $batch_size) {
            $batch_mode = false;
        }

        /** ----------------------------------------
        /**  If batch-mode is not set, send emails
        /** ----------------------------------------*/
        if ($batch_mode == false) {
            $total_sent = $this->deliverManyEmails($email);

            $debug_msg = ee()->email->print_debugger(array());

            $this->deleteAttachments($email); // Remove attachments now

            ee()->view->set_message('success', lang('total_emails_sent') . ' ' . $total_sent, $debug_msg, true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/communicate'));
        }

        if ($batch_size === 0) {
            show_error(lang('batch_size_is_zero'));
        }

        /** ----------------------------------------
        /**  Start Batch-Mode
        /** ----------------------------------------*/
        ee()->view->set_refresh(ee('CP/URL')->make('utilities/communicate/batch/' . $email->cache_id)->compile(), 6, true);

        ee('CP/Alert')->makeStandard('batchmode')
            ->asWarning()
            ->withTitle(lang('batchmode_ready_to_begin'))
            ->addToBody(lang('batchmode_warning'))
            ->defer();

        ee()->functions->redirect(ee('CP/URL')->make('utilities/communicate'));
    }

    /**
     * Batch Email Send
     *
     * Sends email in batch mode
     *
     * @param int $id	The cache_id to send
     */
    public function batch($id)
    {
        ee()->load->library('email');

        if (ee()->config->item('email_batchmode') != 'y') {
            show_error(lang('batchmode_disabled'));
        }

        if (! ctype_digit($id)) {
            show_error(lang('problem_with_id'));
        }

        $email = ee('Model')->get('EmailCache', $id)->first();

        if (is_null($email)) {
            show_error(lang('cache_data_missing'));
        }

        $start = $email->total_sent;

        $this->deliverManyEmails($email);

        if ($email->total_sent == count($email->recipient_array)) {
            $debug_msg = ee()->email->print_debugger(array());

            $this->deleteAttachments($email); // Remove attachments now

            ee()->view->set_message('success', lang('total_emails_sent') . ' ' . $email->total_sent, $debug_msg, true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/communicate'));
        } else {
            $stats = str_replace("%x", ($start + 1), lang('currently_sending_batch'));
            $stats = str_replace("%y", ($email->total_sent), $stats);

            $message = $stats . BR . BR . lang('emails_remaining') . NBS . NBS . (count($email->recipient_array) - $email->total_sent);

            ee()->view->set_refresh(ee('CP/URL')->make('utilities/communicate/batch/' . $email->cache_id)->compile(), 6, true);

            ee('CP/Alert')->makeStandard('batchmode')
                ->asWarning()
                ->withTitle($message)
                ->addToBody(lang('batchmode_warning'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('utilities/communicate'));
        }
    }

    /**
     * Fetches an email from the cache and presents it to the user for re-sending
     *
     * @param int $id	The cache_id to send
     */
    public function resend($id)
    {
        if (! ctype_digit($id)) {
            show_error(lang('problem_with_id'));
        }

        $caches = ee('Model')->get('EmailCache', $id)
            ->with('Roles')
            ->all();

        $email = $caches[0];

        if (is_null($email)) {
            show_error(lang('cache_data_missing'));
        }

        $this->index($email);
    }

    /**
     * Sends a single email handling errors
     *
     * @param	obj		$email	An EmailCache object
     * @param	str		$to		An email address to send to
     * @param	bool	$delete	Delete email attachments after send?
     * @return	str				A response messge as a result of sending the email
     */
    private function deliverOneEmail(EmailCache $email, $to, $delete = true)
    {
        $error = false;

        if (! $this->deliverEmail($email, $to, $email->cc, $email->bcc)) {
            $error = true;
        }

        if ($delete) {
            $this->deleteAttachments($email); // Remove attachments now
        }

        $debug_msg = ee()->email->print_debugger(array());

        if ($error == true) {
            $email->delete();
            show_error(lang('error_sending_email') . BR . BR . $debug_msg);
        }

        $total_sent = 0;

        foreach (array($to, $email->cc, $email->bcc) as $string) {
            if ($string != '') {
                $total_sent += substr_count($string, ',') + 1;
            }
        }

        // Save cache data
        $email->total_sent = $total_sent;
        $email->save();

        return $debug_msg;
    }

    /**
     * Sends multiple emails handling errors
     *
     * @param	obj	$email	An EmailCache object
     * @return	int			The number of emails sent
     */
    private function deliverManyEmails(EmailCache $email)
    {
        $recipient_array = array_slice($email->recipient_array, $email->total_sent);
        $number_to_send = count($recipient_array);

        if ($number_to_send < 1) {
            return 0;
        }

        if (ee()->config->item('email_batchmode') == 'y') {
            $batch_size = (int) ee()->config->item('email_batch_size');

            if ($number_to_send > $batch_size) {
                $number_to_send = $batch_size;
            }
        }

        for ($x = 0; $x < $number_to_send; $x++) {
            $email_address = array_shift($recipient_array);

            if (! $this->deliverEmail($email, $email_address)) {
                $email->delete();

                $debug_msg = ee()->email->print_debugger(array());

                show_error(lang('error_sending_email') . BR . BR . $debug_msg);
            }
            $email->total_sent++;
        }

        $email->save();

        return $email->total_sent;
    }

    /**
     * Delivers an email
     *
     * @param	obj	$email	An EmailCache object
     * @param	str	$to		An email address to send to
     * @param	str	$cc		An email address to cc
     * @param	str	$bcc	An email address to bcc
     * @return	bool		True on success; False on failure
     */
    private function deliverEmail(EmailCache $email, $to, $cc = null, $bcc = null)
    {
        ee()->email->clear(true);
        ee()->email->wordwrap = $email->wordwrap;
        ee()->email->mailtype = $email->mailtype;
        ee()->email->from($email->from_email, $email->from_name);
        ee()->email->to($to);

        if (! is_null($cc)) {
            ee()->email->cc($email->cc);
        }

        if (! is_null($bcc)) {
            ee()->email->bcc($email->bcc);
        }

        ee()->email->subject($this->censorSubject($email));
        ee()->email->message($this->formatMessage($email), $email->plaintext_alt);

        foreach ($email->attachments as $attachment) {
            ee()->email->attach($attachment);
        }

        return ee()->email->send(false);
    }

    /**
     * Formats the message of an email based on the text format type
     *
     * @param	obj	$email	An EmailCache object
     * @return	string		The formatted message
     */
    private function formatMessage(EmailCache $email)
    {
        $message = $email->message;

        if ($email->text_fmt != 'none' && $email->text_fmt != '') {
            ee()->load->library('typography');
            ee()->typography->initialize(array(
                'bbencode_links' => false,
                'parse_images' => false,
                'parse_smileys' => false
            ));

            $message = ee()->typography->parse_type($email->message, array(
                'text_format' => $email->text_fmt,
                'html_format' => 'all',
                'auto_links' => 'n',
                'allow_img_url' => 'y'
            ));
        }

        return $message;
    }

    /**
     * Censors the subject of an email if necessary
     *
     * @param	obj	$email	An EmailCache object
     * @return	string		The censored subject
     */
    private function censorSubject(EmailCache $email)
    {
        $subject = $email->subject;

        if (bool_config_item('enable_censoring')) {
            $subject = (string) ee('Format')->make('Text', $subject)->censor();
        }

        return $subject;
    }

    /**
     * View sent emails
     */
    public function sent()
    {
        if (! ee('Permission')->can('send_cached_email')) {
            show_error(lang('not_allowed_to_email_cache'));
        }

        if (ee()->input->post('bulk_action') == 'remove') {
            ee('Model')->get('EmailCache', ee()->input->get_post('selection'))->all()->delete();
            ee()->view->set_message('success', lang('emails_removed'), '');
        }

        $table = ee('CP/Table');
        $table->setColumns(
            array(
                'subject',
                'date',
                'total_sent',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
                array(
                    'type' => Table::COL_CHECKBOX
                )
            )
        );

        $table->setNoResultsText('no_cached_emails', 'create_new_email', ee('CP/URL')->make('utilities/communicate'));

        $count = 0;

        $emails = ee('Model')->get('EmailCache');

        $filters = ee('CP/Filter')
            ->add('Keyword');

        $search = ee()->input->get_post('filter_by_keyword');
        if (! empty($search)) {
            $emails = $emails->filterGroup()
                ->filter('subject', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('message', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('from_name', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('from_email', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('recipient', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('cc', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->orFilter('bcc', 'LIKE', '%' . ee()->db->escape_like_str($search) . '%')
                ->endFilterGroup();
        }

        $count = $emails->count();
        $filters->add('Perpage', $count);
        $params = $filters->values();

        $page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($page - 1) * $params['perpage']; // Offset is 0 indexed

        $sort_map = array(
            'subject' => 'subject',
            'date' => 'cache_date',
            'total_sent' => 'total_sent',
            'status' => 'status',
        );

        $emails = $emails->order($sort_map[$table->sort_col], $table->sort_dir)
            ->limit($params['perpage'])
            ->offset($offset)
            ->all();

        $vars['emails'] = array();
        $data = array();
        foreach ($emails as $email) {
            // Prepare the $email object for use in the modal
            $email->text_fmt = ($email->text_fmt != 'none') ?: 'br'; // Some HTML formatting for plain text
            $email->subject = htmlentities($this->censorSubject($email), ENT_QUOTES, 'UTF-8');

            $data[] = array(
                $email->subject,
                ee()->localize->human_time($email->cache_date->format('U')),
                $email->total_sent,
                array('toolbar_items' => array(
                    'view' => array(
                        'title' => lang('view_email'),
                        'href' => '',
                        'rel' => 'modal-email-' . $email->cache_id,
                        'class' => 'm-link'
                    ),
                    'sync' => array(
                        'title' => lang('resend'),
                        'href' => ee('CP/URL')->make('utilities/communicate/resend/' . $email->cache_id)
                    )
                )),
                array(
                    'name' => 'selection[]',
                    'value' => $email->cache_id,
                    'data' => array(
                        'confirm' => lang('view_email_cache') . ': <b>' . $email->subject . '(x' . $email->total_sent . ')</b>'
                    )
                )
            );

            ee()->load->library('typography');
            ee()->typography->initialize(array(
                'bbencode_links' => false,
                'parse_images' => false,
                'parse_smileys' => false
            ));

            $email->message = ee()->typography->parse_type($email->message, array(
                'text_format' => ($email->text_fmt == 'markdown') ? 'markdown' : 'xhtml',
                'html_format' => 'all',
                'auto_links' => 'n',
                'allow_img_url' => 'y'
            ));

            $vars['emails'][] = $email;
        }

        $table->setData($data);

        $base_url = ee('CP/URL')->make('utilities/communicate/sent');
        $vars['table'] = $table->viewData($base_url);
        ee()->view->filters = $filters->render($base_url);
        $base_url->addQueryStringVariables($params);

        $vars['pagination'] = ee('CP/Pagination', $count)
            ->perPage($params['perpage'])
            ->currentPage($page)
            ->render($base_url);

        ee()->view->cp_page_title = lang('view_email_cache');

        // Set search results heading
        if (! empty($search)) {
            ee()->view->cp_heading = sprintf(
                lang('search_results_heading'),
                $count,
                htmlspecialchars($search, ENT_QUOTES, 'UTF-8')
            );
        }

        ee()->javascript->set_global('lang.remove_confirm', lang('view_email_cache') . ': <b>### ' . lang('emails') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array('cp/confirm_remove'),
        ));

        ee()->view->cp_breadcrumbs = array(
            '' => lang('view_email_cache')
        );

        ee()->cp->render('utilities/communicate/sent', $vars);
    }

    /**
     * Check for recipients
     *
     * An internal validation function for callbacks
     *
     * @param	string
     * @return	bool
     */
    public function _check_for_recipients($str)
    {
        if (! $str && ee()->input->post('total_gl_recipients') < 1) {
            ee()->form_validation->set_message('_check_for_recipients', lang('required'));

            return false;
        }

        return true;
    }

    /**
     * Attachment Handler
     *
     * Used to manage and validate attachments. Must remain public,
     * it's a form validation callback.
     *
     * @return	bool
     */
    public function _attachment_handler()
    {
        // File Attachments?
        if (! isset($_FILES['attachment']['name']) or empty($_FILES['attachment']['name'])) {
            return true;
        }

        ee()->load->library('upload');
        ee()->upload->initialize(array(
            'allowed_types' => '*',
            'use_temp_dir' => true
        ));

        if (! ee()->upload->do_upload('attachment')) {
            ee()->form_validation->set_message('_attachment_handler', lang('attachment_problem'));

            return false;
        }

        $data = ee()->upload->data();

        $this->attachments[] = $data['full_path'];

        return true;
    }

    /**
     * Delete Attachments
     */
    private function deleteAttachments($email)
    {
        foreach ($email->attachments as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $email->attachments = array();
        $email->save();
    }
}
// END CLASS

// EOF
