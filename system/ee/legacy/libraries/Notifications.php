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
 * Core Notifications
 */
class Notifications
{
    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->load->library('api');
        ee()->load->library('email');
    }

    /**
     * Send admin notification
     *
     * Sends an admin notification email
     *
     * @access	public
     * @param	string
     * @param	int
     * @param	int
     */
    public function send_admin_notification($notify_address, $channel_id, $entry_id)
    {
        ee()->legacy_api->instantiate('channel_structure');
        ee()->load->model('channel_entries_model');

        $e = ee()->channel_entries_model->get_entry($entry_id, $channel_id);
        $c = ee()->api_channel_structure->get_channel_info($channel_id);
        $overrides = ee()->config->get_cached_site_prefs($c->row('site_id'));

        $swap = array(
            'name' => ee()->session->userdata('screen_name'),
            'email' => ee()->session->userdata('email'),
            'channel_name' => $c->row('channel_title'),
            'entry_title' => $e->row('title'),
            'entry_url' => reduce_double_slashes(parse_config_variables($c->row('channel_url'), $overrides) . '/' . $e->row('url_title')),
            'comment_url' => reduce_double_slashes(parse_config_variables($c->row('comment_url'), $overrides) . '/' . $e->row('url_title')),
            'cp_edit_entry_url' => ee('CP/URL')->make(
                'publish/edit/entry/' . $entry_id,
                array(),
                ee()->config->item('cp_url')
            )
        );

        $template = ee()->functions->fetch_email_template('admin_notify_entry');
        $email_tit = ee()->functions->var_swap($template['title'], $swap);
        $email_msg = ee()->functions->var_swap($template['data'], $swap);

        // We don't want to send a notification to the user
        // triggering the event

        if ( ! empty(ee()->session->userdata('email')) && strpos($notify_address, ee()->session->userdata('email')) !== false) {
            $notify_address = str_replace(ee()->session->userdata('email'), "", $notify_address);
        }

        $notify_address = reduce_multiples($notify_address, ',', true);

        if ($notify_address != '') {
            //	Send email
            ee()->load->library('email');
            ee()->load->helper('text_helper');

            $email_msg = entities_to_ascii($email_msg);

            // interim step for a bug fix where this notification was ignoring the mail format preference
            // This makes this as close to a non-breaking change in the middle of v4 that we can.
            // v5 can contain a proper change to notification templates that would require some user action when updating.
            //
            // To use their mail format setting of HTML, and if their template contains no markup,
            // we need to preserve linebreaks. If they are already trying to use HTML in it, leave their
            // template alone, hence the strip_tags() check.
            if (ee()->config->item('mail_format') == 'html' && $email_msg == strip_tags($email_msg)) {
                $email_msg = str_replace("\n", "<br>\n", $email_msg);
            }

            foreach (explode(',', $notify_address) as $addy) {
                ee()->email->EE_initialize();
                ee()->email->wordwrap = false;
                ee()->email->mailtype = ee()->config->item('mail_format');
                ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
                ee()->email->to($addy);
                ee()->email->reply_to(ee()->config->item('webmaster_email'));
                ee()->email->subject($email_tit);
                ee()->email->message($email_msg);
                ee()->email->send();
            }
        }
    }

    /**
     * Send checksum notification
     *
     * Sends a notification email to the webmaster if a bootstrap file
     * was changed.
     *
     * @access	public
     * @param	string
     * @param	int
     * @param	int
     */
    public function send_checksum_notification($changed)
    {
        //	Send email
        ee()->load->library('email');
        ee()->load->helper('text');

        $subject = ee()->lang->line('checksum_email_subject');
        $message = ee()->lang->line('checksum_email_message');

        $message = str_replace(
            array('{url}', '{changed}'),
            array(ee()->config->item('base_url'), implode("\n", $changed)),
            $message
        );

        ee()->email->EE_initialize();
        ee()->email->wordwrap = false;
        ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
        ee()->email->to(ee()->config->item('webmaster_email'));
        ee()->email->reply_to(ee()->config->item('webmaster_email'));
        ee()->email->subject($subject);
        ee()->email->message(entities_to_ascii($message));
        ee()->email->send();
    }
}

// END Notifications class

// EOF
