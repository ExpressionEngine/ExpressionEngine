<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Moblog Module
 */
class Moblog
{
    public $cache_name = 'moblog_cache';		// Name of cache directory
    public $url_title_word = 'moblog';				// If duplicate url title, this is added along with number
    public $message_array = array();				// Array of return messages
    public $return_data = ''; 					// When silent mode is off
    public $silent = ''; 					// yes/no (string) - Returns error information
    public $moblog_array = array(); 				// Row information for moblog being processed

    public $fp = ''; 					// fopen resource
    public $pop_newline = "\n";					// Newline for POP Server. Switch to \r\n for Microsoft servers
    public $total_size = 0;					// Total size of emails being checked in bytes
    public $checked_size = 0;					// Accumulated size of emails checked thus far in bytes
    public $max_size = 5;					// Maximum amount of email to check, in MB
    public $email_sizes = array();				// The sizes of the new emails being checked, in bytes

    public $boundary = false; 				// Boundary marker in emails
    public $multi_boundary = '';					// Boundary for multipart content types
    public $newline = '1n2e3w4l5i6n7e8'; 	// Newline replacement
    public $charset = 'auto';				// Character set for main body of email

    public $author = '';					// Author of current email being processed
    public $body = '';					// Main text contents of email being processed
    public $sender_email = '';					// Email address that sent email
    public $uploads = 0;					// Number of file uploads for this check
    public $email_files = array();				// Array containing filenames of uploads for this email
    public $emails_done = 0;					// Number of emails processed
    public $entries_added = 0;					// Number of entries added
    public $upload_dir_code = '';					// {filedir_2} for entry's
    public $upload_path = '';					// Server path for upload directory
    public $entry_data = array();				// Data for entry's custom fields
    public $post_data = array();				// Post data retrieved from email being processed: Subject, IP, Categories, Status
    public $template = '';					// Moblog's template
    public $sticky = 'n';					// Default Sticky Value

    // These settings are for a specific problem with AT&T phones
    public $attach_as_txt = false;				// Email's Message as txt file?
    public $attach_text = '';					// If $attach_as_txt is true, this is the text
    public $attach_name = '';					// If $attach_as_txt is true, this is the name

    public $time_offset = '5';					// Number of seconds entries are offset by negatively, higher if you are putting in many entries

    public $movie = array();				// Suffixes for accepted movie files
    public $audio = array();				// Suffixes for accepted audio files
    public $image = array();				// Suffixes for accepted image files
    public $files = array();				// Suffixes for other types of accepted files

    public $txt_override = false;				// When set to TRUE, all .txt files are treated as message text

    /**
     * 	Constructor
     */
    public function __construct()
    {
        /** -----------------------------
        /**  Default file formats
        /** -----------------------------*/
        $this->movie = array('3gp','mov','mpg','avi','movie');
        $this->audio = array('mid','midi','mp2','mp3','aac','mp4','aif','aiff','aifc','ram','rm','rpm','wav','ra','rv','wav');
        $this->image = array('bmp','gif','jpeg','jpg','jpe','png','tiff','tif');
        $this->files = array('doc','xls','zip','tar','tgz','swf','sit','php','txt','html','asp','js','rtf', 'pdf');

        if (! defined('LD')) {
            define('LD', '{');
        }

        if (! defined('RD')) {
            define('RD', '}');
        }

        if (! defined('SLASH')) {
            define('SLASH', '&#47;');
        }

        $this->max_size = $this->max_size * 1024 * 1000;
    }

    /**
     * 	Check for Expired Moblogs
     */
    public function check()
    {
        $which = ee()->TMPL->fetch_param('which', '');
        $silent = ee()->TMPL->fetch_param('silent', 'yes');

        // Backwards compatible with previously documented "true/false" parameters (now "yes/no")
        $this->silent = ($silent == 'true' or $silent == 'yes') ? 'yes' : 'no';

        if ($which == '') {
            $this->return_data = ($this->silent == 'yes') ? '' : 'No Moblog Indicated';

            return $this->return_data ;
        }

        ee()->lang->loadfile('moblog');

        $sql = "SELECT * FROM exp_moblogs WHERE moblog_enabled = 'y'";
        $sql .= ($which == 'all') ? '' : ee()->functions->sql_andor_string($which, 'moblog_short_name', 'exp_moblogs');
        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            $this->return_data = ($this->silent == 'yes') ? '' : lang('no_moblogs');

            return $this->return_data;
        }

        $expired = array();

        ee()->load->driver('cache');

        foreach ($query->result_array() as $row) {
            $data = ee()->cache->get('/moblog/' . $row['moblog_id'], Cache::GLOBAL_SCOPE);

            if ($data === false) {
                ee()->cache->save('/moblog/' . $row['moblog_id'], 'hi', $row['moblog_time_interval'] * 60, Cache::GLOBAL_SCOPE);
                $expired[] = $row['moblog_id'];
            }
        }

        if (count($expired) == 0) {
            $this->return_data = ($this->silent == 'yes') ? '' : lang('moblog_current');

            return $this->return_data;
        }

        /** ------------------------------
        /**  Process Expired Moblogs
        /** ------------------------------*/
        foreach ($query->result_array() as $row) {
            if (in_array($row['moblog_id'], $expired)) {
                $this->moblog_array = $row;

                if ($this->moblog_array['moblog_email_type'] == 'imap') {
                    if (! $this->check_imap_moblog()) {
                        if ($this->silent == 'no' && count($this->message_array) > 0) {
                            $this->return_data .= '<p><strong>' . $this->moblog_array['moblog_full_name'] . '</strong><br />' .
                                        $this->errors() . "\n</p>";
                        }
                    }
                } else {
                    if (! $this->check_pop_moblog()) {
                        if ($this->silent == 'no' && count($this->message_array) > 0) {
                            $this->return_data .= '<p><strong>' . $this->moblog_array['moblog_full_name'] . '</strong><br />' .
                                        $this->errors() . "\n</p>";
                        }
                    }
                }

                $this->message_array = array();
            }
        }

        if ($this->silent == 'no') {
            $this->return_data .= lang('moblog_successful_check') . "<br />\n";
            $this->return_data .= lang('emails_done') . " {$this->emails_done}<br />\n";
            $this->return_data .= lang('entries_added') . " {$this->entries_added}<br />\n";
            $this->return_data .= lang('attachments_uploaded') . " {$this->uploads}<br />\n";
        }

        return $this->return_data ;
    }

    /** -------------------------------------
    /**  Return errors
    /** -------------------------------------*/
    public function errors()
    {
        $message = '';

        if (count($this->message_array) == 0 or $this->silent == 'yes') {
            return $message;
        }

        foreach ($this->message_array as $row) {
            $message .= ($message == '') ? '' : "<br />\n";
            $message .= (! lang($row)) ? $row : lang($row);
        }

        return $message;
    }

    /**
     * 	Check Pop3 Moblog
     *
     *
     */
    public function check_pop_moblog()
    {
        /** ------------------------------
        /**  Email Login Check
        /** ------------------------------*/
        $port = 110;
        $ssl = (substr($this->moblog_array['moblog_email_server'], 0, 6) == 'ssl://');

        if ($ssl or stripos($this->moblog_array['moblog_email_server'], 'gmail') !== false) {
            if (! $ssl) {
                $this->moblog_array['moblog_email_server'] = 'ssl://' . $this->moblog_array['moblog_email_server'];
            }

            $port = 995;
        }

        if (! $this->fp = @fsockopen($this->moblog_array['moblog_email_server'], $port, $errno, $errstr, 20)) {
            $this->message_array[] = 'no_server_connection';

            return false;
        }

        if (strncasecmp(fgets($this->fp, 1024), '+OK', 3) != 0) {
            $this->message_array[] = 'invalid_server_response';
            @fclose($this->fp);

            return false;
        }

        if (strncasecmp($this->pop_command("USER " . base64_decode($this->moblog_array['moblog_email_login'])), '+OK', 3) != 0) {
            // Windows servers something require a different line break.
            // So, we change the line break and try again.

            $this->pop_newline = "\r\n";

            if (strncasecmp($this->pop_command("USER " . base64_decode($this->moblog_array['moblog_email_login'])), '+OK', 3) != 0) {
                $this->message_array[] = 'invalid_username';
                $line = $this->pop_command("QUIT");
                @fclose($this->fp);

                return false;
            }
        }

        if (strncasecmp($this->pop_command("PASS " . base64_decode($this->moblog_array['moblog_email_password'])), '+OK', 3) != 0) {
            $this->message_array[] = 'invalid_password';
            $line = $this->pop_command("QUIT");
            @fclose($this->fp);

            return false;
        }

        /** ------------------------------
        /**  Got Mail?
        /** ------------------------------*/
        $this->pop_newline = "\r\n";
        if (! $line = $this->pop_command("STAT")) {
            $this->message_array[] = 'unable_to_retrieve_emails';
            $line = $this->pop_command("QUIT");
            @fclose($this->fp);

            return false;
        }

        $stats = explode(" ", $line);
        $total = (! isset($stats['1'])) ? 0 : $stats['1'];
        $this->total_size = (! isset($stats['2'])) ? 0 : $stats['2'];

        if ($total == 0) {
            $this->message_array[] = 'no_valid_emails';
            $line = $this->pop_command("QUIT");
            @fclose($this->fp);

            return;
        }

        /** ------------------------------
        /**  Determine Sizes of Emails
        /** ------------------------------*/
        if ($this->total_size > $this->max_size) {
            if (! $line = $this->pop_command("LIST")) {
                $this->message_array[] = 'unable_to_retrieve_emails';
                $line = $this->pop_command("QUIT");
                @fclose($this->fp);

                return false;
            }

            do {
                $data = fgets($this->fp, 1024);
                $data = $this->iso_clean($data);

                if (empty($data) or trim($data) == '.') {
                    break;
                }

                $x = explode(' ', $data);

                if (count($x) == 1) {
                    break;
                }

                $this->email_sizes[$x['0']] = $x['1'];
            } while (strncmp($data, ".\r\n", 3) != 0);
        }

        /** ------------------------------
        /**  Find Valid Emails
        /** ------------------------------*/
        $valid_emails = array();
        $valid_froms = explode("|", $this->moblog_array['moblog_valid_from']);

        for ($i = 1; $i <= $total; $i++) {
            if (strncasecmp($this->pop_command("TOP {$i} 0"), '+OK', 3) != 0) {
                $line = $this->pop_command("QUIT");
                @fclose($this->fp);

                return false;
            }

            $valid_subject = 'n';
            $valid_from = ($this->moblog_array['moblog_valid_from'] != '') ? 'n' : 'y';
            $str = fgets($this->fp, 1024);

            while (strncmp($str, ".\r\n", 3) != 0) {
                $str = fgets($this->fp, 1024);
                $str = $this->iso_clean($str);

                if (empty($str)) {
                    break;
                }

                // ------------------------
                // Does email contain correct prefix? (if prefix is set)
                // Liberal interpretation of prefix location
                // ------------------------

                if ($this->moblog_array['moblog_subject_prefix'] == '') {
                    $valid_subject = 'y';
                } elseif (preg_match("/Subject:(.*)/", $str, $subject)) {
                    if (strpos(trim($subject['1']), $this->moblog_array['moblog_subject_prefix']) !== false) {
                        $valid_subject = 'y';
                    }
                }

                if ($this->moblog_array['moblog_valid_from'] != '') {
                    if (preg_match("/From:\s*(.*)\s*\<(.*)\>/", $str, $from) or preg_match("/From:\s*(.*)\s*/", $str, $from)) {
                        $address = (! isset($from['2'])) ? $from['1'] : $from['2'];

                        if (in_array(trim($address), $valid_froms)) {
                            $valid_from = 'y';
                        }
                    }
                }
            }

            if ($valid_subject == 'y' && $valid_from == 'y') {
                $valid_emails[] = $i;
            }
        }

        unset($subject);
        unset($str);

        if (count($valid_emails) == 0) {
            $this->message_array[] = 'no_valid_emails';
            $line = $this->pop_command("QUIT");
            @fclose($this->fp);

            return;
        }

        /** ------------------------------
        /**  Process Valid Emails
        /** ------------------------------*/
        foreach ($valid_emails as $email_id) {
            // Reset Variables
            $this->post_data = array();
            $this->email_files = array();
            $this->body = '';
            $this->sender_email = '';
            $this->entry_data = array();
            $email_data = '';
            $this->attach_as_txt = false;

            /** ------------------------------------------
            /**  Do Not Exceed Max Size During a Moblog Check
            /** ------------------------------------------*/
            if ($this->total_size > $this->max_size && isset($this->email_sizes[$email_id])) {
                if ($this->checked_size + $this->email_sizes[$email_id] > $this->max_size) {
                    continue;
                }

                $this->checked_size += $this->email_sizes[$email_id];
            }

            /** ---------------------------------------
            /**  Failure does happen at times
            /** ---------------------------------------*/
            if (strncasecmp($this->pop_command("RETR {$email_id}"), '+OK', 3) != 0) {
                continue;
            }

            // Under redundant, see redundant
            $this->post_data['subject'] = 'Moblog Entry';
            $this->post_data['ip'] = '127.0.0.1';
            $format_flow = 'n';

            /** ------------------------------
            /**  Retrieve Email data
            /** ------------------------------*/
            do {
                $data = fgets($this->fp, 1024);
                $data = $this->iso_clean($data);

                if (empty($data)) {
                    break;
                }

                if ($format_flow == 'n' && stristr($data, 'format=flowed')) {
                    $format_flow = 'y';
                }

                $email_data .= $data;
            } while (strncmp($data, ".\r\n", 3) != 0);

            //echo $email_data."<br /><br />\n\n";

            if (preg_match("/charset=(.*?)(\s|" . $this->newline . ")/is", $email_data, $match)) {
                $this->charset = trim(str_replace(array("'", '"', ';'), '', $match['1']));
            }

            /** --------------------------
            /**  Set Subject, Remove Moblog Prefix
            /** --------------------------*/
            if (preg_match("/Subject:(.*)/", trim($email_data), $subject)) {
                if ($this->moblog_array['moblog_subject_prefix'] == '') {
                    $this->post_data['subject'] = (trim($subject['1']) != '') ? trim($subject['1']) : 'Moblog Entry';
                } elseif (strpos(trim($subject['1']), $this->moblog_array['moblog_subject_prefix']) !== false) {
                    $str_subject = str_replace($this->moblog_array['moblog_subject_prefix'], '', $subject['1']);
                    $this->post_data['subject'] = (trim($str_subject) != '') ? trim($str_subject) : 'Moblog Entry';
                }

                // If the subject header was read with imap_utf8() in the iso_clean() method, then
                // we don't need to do anything further
                if (! function_exists('imap_utf8')) {
                    // If subject header was processed with MB or Iconv functions, then the internal encoding
                    // must be used to decode the subject, not the charset used by the email
                    if (function_exists('mb_convert_encoding')) {
                        $this->post_data['subject'] = mb_convert_encoding($this->post_data['subject'], strtoupper(ee()->config->item('charset')), mb_internal_encoding());
                    } elseif (function_exists('iconv')) {
                        $this->post_data['subject'] = iconv(iconv_get_encoding('internal_encoding'), strtoupper(ee()->config->item('charset')), $this->post_data['subject']);
                    } elseif (strtolower(ee()->config->item('charset')) == 'utf-8' && strtolower($this->charset) == 'iso-8859-1') {
                        $this->post_data['subject'] = utf8_encode($this->post_data['subject']);
                    } elseif (strtolower(ee()->config->item('charset')) == 'iso-8859-1' && strtolower($this->charset) == 'utf-8') {
                        $this->post_data['subject'] = utf8_decode($this->post_data['subject']);
                    }
                }
            }

            /** --------------------------
            /**  IP Address of Sender
            /** --------------------------*/
            if (preg_match("/Received:\s*from\s*(.*)\[+(.*)\]+/", $email_data, $subject)) {
                if (isset($subject['2']) && ee()->input->valid_ip(trim($subject['2']))) {
                    $this->post_data['ip'] = trim($subject['2']);
                }
            }

            /** --------------------------
            /**  Check if AT&T email
            /** --------------------------*/
            if (preg_match("/From:\s*(.*)\s*\<(.*)\>/", $email_data, $from) or preg_match("/From:\s*(.*)\s*/", $email_data, $from)) {
                $this->sender_email = (! isset($from['2'])) ? $from['1'] : $from['2'];

                if (strpos(trim($this->sender_email), 'mobile.att.net') !== false) {
                    $this->attach_as_txt = true;
                }
            }

            /** -------------------------------------
            /**  Eliminate new line confusion
            /** -------------------------------------*/
            $email_data = $this->remove_newlines($email_data, $this->newline);

            /** -------------------------------------
            /**  Determine Boundary
            /** -------------------------------------*/
            if (! $this->find_boundary($email_data)) { // OR $this->moblog_array['moblog_upload_directory'] == '0')
                // Figure out content type and subtype
                $contents = $this->find_data($email_data, "Content-Type: ", $this->newline);
                $x = explode(';', $contents);
                $content_type = strtolower($x['0']);
                $pieces = explode('/', trim($content_type));
                $type = trim($pieces['0']);
                $subtype = (! isset($pieces['1'])) ? '0' : trim($pieces['1']);

                if ($type == 'image' or $type == 'application' or $type == 'audio' or $type == 'video') {
                    /** -------------------------
                    /**  No text, just files
                    /** -------------------------*/
                    $this->body = '';

                    if (! $this->_process_attachment($email_data, $type, $subtype)) {
                        $this->message_array[] = 'unable_to_parse';

                        return false;
                    }
                } else {
                    /** -------------------------
                    /**  No files, just text
                    /** -------------------------*/
                    $duo = $this->newline . $this->newline;
                    $this->body = $this->find_data($email_data, $duo, $duo . '.' . $this->newline);

                    if ($this->body == '') {
                        $this->body = $this->find_data($email_data, $duo, $this->newline . '.' . $this->newline);
                    }

                    // Check for Quoted-Printable and Base64 encoding
                    if (stristr($email_data, 'Content-Transfer-Encoding')) {
                        $encoding = $this->find_data($email_data, "Content-Transfer-Encoding: ", $this->newline);

                        if (! stristr(trim($encoding), "quoted-printable") and ! stristr(trim($encoding), "base64")) {
                            // try it without the space after the colon...
                            $encoding = $this->find_data($email_data, "Content-Transfer-Encoding:", $this->newline);
                        }

                        if (stristr(trim($encoding), "quoted-printable")) {
                            $this->body = str_replace($this->newline, "\n", $this->body);
                            $this->body = quoted_printable_decode($this->body);
                            $this->body = (substr($this->body, 0, 1) != '=') ? $this->body : substr($this->body, 1);
                            $this->body = (substr($this->body, -1) != '=') ? $this->body : substr($this->body, 0, -1);
                            $this->body = $this->remove_newlines($this->body, $this->newline);
                        } elseif (stristr(trim($encoding), "base64")) {
                            $this->body = str_replace($this->newline, "\n", $this->body);
                            $this->body = base64_decode(trim($this->body));
                            $this->body = $this->remove_newlines($this->body, $this->newline);
                        }
                    }

                    if ($this->charset != ee()->config->item('charset')) {
                        if (function_exists('mb_convert_encoding')) {
                            $this->body = mb_convert_encoding($this->body, strtoupper(ee()->config->item('charset')), strtoupper($this->charset));
                        } elseif (function_exists('iconv') and ($iconvstr = @iconv(strtoupper($this->charset), strtoupper(ee()->config->item('charset')), $this->body)) !== false) {
                            $this->body = $iconvstr;
                        } elseif (strtolower(ee()->config->item('charset')) == 'utf-8' && strtolower($this->charset) == 'iso-8859-1') {
                            $this->body = utf8_encode($this->body);
                        } elseif (strtolower(ee()->config->item('charset')) == 'iso-8859-1' && strtolower($this->charset) == 'utf-8') {
                            $this->body = utf8_decode($this->body);
                        }
                    }
                }
            } else {
                if (! $this->parse_email($email_data)) {
                    $this->message_array[] = 'unable_to_parse';

                    return false;
                }

                // Email message as .txt file?
                // Make the email body the attachment's contents
                // Unset attachment from files array.
                if ($this->attach_as_txt === true && trim($this->body) == '' && $this->attach_text != '') {
                    $this->body = $this->attach_text;
                    $this->attach_text = '';

                    foreach ($this->post_data['files'] as $key => $value) {
                        if ($value == $this->attach_name) {
                            unset($this->post_data['files'][$key]);
                        }
                    }
                }
            }

            /** ---------------------------
            /**  Authorization Check
            /** ---------------------------*/
            if (! $this->check_login()) {
                if ($this->moblog_array['moblog_auth_required'] == 'y') {
                    /** -----------------------------
                    /**  Delete email?
                    /** -----------------------------*/
                    if ($this->moblog_array['moblog_auth_delete'] == 'y' && strncasecmp($this->pop_command("DELE {$email_id}"), '+OK', 3) != 0) {
                        $this->message_array[] = 'undeletable_email'; //.$email_id;

                        return false;
                    }

                    /** -----------------------------
                    /**  Delete any uploaded images
                    /** -----------------------------*/
                    if (count($this->email_files) > 0) {
                        foreach ($this->email_files as $axe) {
                            @unlink($this->upload_path . $axe);
                        }
                    }

                    // Error...
                    $this->message_array[] = 'authorization_failed';
                    $this->message_array[] = $this->post_data['subject'];

                    continue;
                }
            }

            /** -----------------------------
            /**  Format Flow Fix - Oh Joy!
            /** -----------------------------*/
            if ($format_flow == 'y') {
                $x = explode($this->newline, $this->body);
                $wrap_point = 10;

                if (count($x) > 1) {
                    $this->body = '';

                    // First, find wrap point
                    for ($p = 0; $p < count($x); $p++) {
                        $wrap_point = (strlen($x[$p]) > $wrap_point) ? strlen($x[$p]) : $wrap_point;
                    }

                    // Unwrap the Content
                    for ($p = 0; $p < count($x); $p++) {
                        $next = (isset($x[$p + 1]) && count($y = explode(' ', $x[$p + 1]))) ? $y['0'] : '';
                        $this->body .= (strlen($x[$p]) < $wrap_point && strlen($x[$p] . $next) <= $wrap_point) ? $x[$p] . $this->newline : $x[$p];
                    }
                }
            }

            $allow_overrides = (! isset($this->moblog_array['moblog_allow_overrides'])) ? 'y' : $this->moblog_array['moblog_allow_overrides'];

            /** -----------------------------
            /**  Image Archive set in email?
            /** -----------------------------*/
            if ($allow_overrides == 'y' &&
                (preg_match("/\{file_archive\}(.*)\{\/file_archive\}/s", $this->body, $matches) or
                 preg_match("/\<file_archive\>(.*)\<\/file_archive\>/s", $this->body, $matches))) {
                $matches['1'] = trim($matches['1']);

                if ($matches['1'] == 'y' or $matches['1'] == 'true' or $matches['1'] == '1') {
                    $this->moblog_array['moblog_file_archive'] = 'y';
                } else {
                    $this->moblog_array['moblog_file_archive'] = 'n';
                }

                $this->body = str_replace($matches['0'], '', $this->body);
            }

            /** -----------------------------
            /**  Categories set in email?
            /** -----------------------------*/
            if ($allow_overrides == 'n' or (! preg_match("/\{category\}(.*)\{\/category\}/s", $this->body, $cats) &&
                                             ! preg_match("/\<category\>(.*)\<\/category\>/s", $this->body, $cats))) {
                $this->post_data['categories'] = trim($this->moblog_array['moblog_categories']);
            } else {
                $cats['1'] = str_replace(':', '|', $cats['1']);
                $cats['1'] = str_replace(',', '|', $cats['1']);
                $this->post_data['categories'] = trim($cats['1']);
                $this->body = str_replace($cats['0'], '', $this->body);
            }

            /** -----------------------------
            /**  Status set in email
            /** -----------------------------*/
            if ($allow_overrides == 'n' or (! preg_match("/\{status\}(.*)\{\/status\}/s", $this->body, $cats) &&
                                             ! preg_match("/\<status\>(.*)\<\/status\>/s", $this->body, $cats))) {
                $this->post_data['status'] = trim($this->moblog_array['moblog_status']);
            } else {
                $this->post_data['status'] = trim($cats['1']);
                $this->body = str_replace($cats['0'], '', $this->body);
            }

            /** -----------------------------
            /**  Sticky Set in Email
            /** -----------------------------*/
            if ($allow_overrides == 'n' or (! preg_match("/\{sticky\}(.*)\{\/sticky\}/s", $this->body, $mayo) &&
                                             ! preg_match("/\<sticky\>(.*)\<\/sticky\>/s", $this->body, $mayo))) {
                $this->post_data['sticky'] = (! isset($this->moblog_array['moblog_sticky_entry'])) ? $this->sticky : $this->moblog_array['moblog_sticky_entry'];
            } else {
                $this->post_data['sticky'] = (trim($mayo['1']) == 'yes' or trim($mayo['1']) == 'y') ? 'y' : 'n';
                $this->body = str_replace($mayo['0'], '', $this->body);
            }

            /** -----------------------------
            /**  Default Field set in email?
            /** -----------------------------*/
            if ($allow_overrides == 'y' && (preg_match("/\{field\}(.*)\{\/field\}/s", $this->body, $matches) or
                                            preg_match("/\<field\>(.*)\<\/field\>/s", $this->body, $matches))) {
                $matches[1] = trim($matches[1]);

                ee()->db->select('field_id');
                ee()->db->from('channel_fields');
                ee()->db->where('(channel_fields.field_name = "' . $matches[1] . '" OR ' . ee()->db->dbprefix('channel_fields') . '.field_label = "' . $matches[1] . '")', null, false);

                /* -------------------------------------
                /*  Hidden Configuration Variable
                /*  - moblog_allow_nontextareas => Removes the textarea only restriction
                /*	for custom fields in the moblog module (y/n)
                /* -------------------------------------*/
                if (ee()->config->item('moblog_allow_nontextareas') != 'y') {
                    ee()->db->where('channel_fields.field_type', 'textarea');
                }

                $results = ee()->db->get();

                if ($results->num_rows() > 0) {
                    $this->moblog_array['moblog_field_id'] = trim($results->row('field_id'));
                }

                $this->body = str_replace($matches['0'], '', $this->body);
            }

            /** -----------------------------
            /**  Set Entry Title in Email
            /** -----------------------------*/
            if (preg_match("/\{entry_title\}(.*)\{\/entry_title\}/", $this->body, $matches) or preg_match("/\<entry_title\>(.*)\<\/entry_title\>/", $this->body, $matches)) {
                if (strlen($matches['1']) > 1) {
                    $this->post_data['subject'] = trim(str_replace($this->newline, "\n", $matches['1']));
                }

                $this->body = str_replace($matches['0'], '', $this->body);
            }

            /** ----------------------------
            /**  Post Entry
            /** ----------------------------*/
            if ($this->moblog_array['moblog_channel_id'] != '0' && $this->moblog_array['moblog_file_archive'] == 'n') {
                $this->template = $this->moblog_array['moblog_template'];

                $tag = 'field';

                if ($this->moblog_array['moblog_field_id'] != 'none' or
                    preg_match("/" . LD . 'field:' . "(.*?)" . RD . "(.*?)" . LD . '\/' . 'field:' . "(.*?)" . RD . "/s", $this->template, $matches) or
                    preg_match("/[\<\{]field\:(.*?)[\}\>](.*?)[\<\{]\/field\:(.*?)[\}\>]/", $this->body, $matches)
                    ) {
                    $this->post_entry();
                } else {
                    $this->emails_done++;

                    continue;
                }
            }

            /** -------------------------
            /**  Delete Email
            /** -------------------------*/
            if (strncasecmp($this->pop_command("DELE {$email_id}"), '+OK', 3) != 0) {
                $this->message_array[] = 'undeletable_email'; //.$email_id;

                return false;
            }

            $this->emails_done++;
        }

        /** -----------------------------
        /**  Close Email Connection
        /** -----------------------------*/
        $line = $this->pop_command("QUIT");

        @fclose($this->fp);

        /** ---------------------------------
        /**  Clear caches if needed
        /** ---------------------------------*/
        if ($this->emails_done > 0) {
            if (ee()->config->item('new_posts_clear_caches') == 'y') {
                ee()->functions->clear_caching('all');
            } else {
                ee()->functions->clear_caching('sql');
            }
        }

        return true;
    }

    /**
     * 	Post Entry
     */
    public function post_entry()
    {
        // Default Channel Data

        $channel_id = $this->moblog_array['moblog_channel_id'];

        ee()->db->select();
        $query = ee()->db->get_where('channels', array('channel_id' => $channel_id));

        if ($query->num_rows() == 0) {
            $this->message_array[] = 'invalid_channel'; // How the hell did this happen?

            return false;
        }

        $site_id = $query->row('site_id');
        $notify_address = ($query->row('channel_notify') == 'y' and $query->row('channel_notify_emails') != '') ? $query->row('channel_notify_emails') : '';

        // Collect the meta data

        $this->post_data['subject'] = strip_tags($this->post_data['subject']);

        $this->moblog_array['moblog_author_id'] = ($this->moblog_array['moblog_author_id'] == 'none') ? '1' : $this->moblog_array['moblog_author_id'];
        $author_id = ($this->author != '') ? $this->author : $this->moblog_array['moblog_author_id'];

        if (! is_numeric($author_id) or $author_id == '0') {
            $author_id = '1';
        }

        // Load the text helper
        ee()->load->helper('text');
        $entry_date = (ee()->localize->now + $this->entries_added - $this->time_offset);

        $data = array(
            'channel_id' => $channel_id,
            'site_id' => $site_id,
            'author_id' => $author_id,
            'title' => (ee()->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities($this->post_data['subject']) : $this->post_data['subject'],
            'ip_address' => $this->post_data['ip'],
            'entry_date' => $entry_date,
            'edit_date' => gmdate("YmdHis", $entry_date),
            'year' => gmdate('Y', $entry_date),
            'month' => gmdate('m', $entry_date),
            'day' => gmdate('d', $entry_date),
            'sticky' => (isset($this->post_data['sticky'])) ? $this->post_data['sticky'] : $this->sticky,
            'status' => ($this->post_data['status'] == 'none') ? 'open' : $this->post_data['status'],
            'allow_comments' => $query->row('deft_comments')
        );
        $entry = ee('Model')->make('ChannelEntry');
        $entry->channel_id = $channel_id;

        // Remove ignore text

        $this->body = preg_replace("#<img\s+src=\s*[\"']cid:(.*?)\>#si", '', $this->body);  // embedded images

        $this->moblog_array['moblog_ignore_text'] = $this->remove_newlines($this->moblog_array['moblog_ignore_text'], $this->newline);

        // One biggo chunk
        if ($this->moblog_array['moblog_ignore_text'] != '' && stristr($this->body, $this->moblog_array['moblog_ignore_text']) !== false) {
            $this->body = str_replace($this->moblog_array['moblog_ignore_text'], '', $this->body);
        } elseif ($this->moblog_array['moblog_ignore_text'] != '') {
            // By line
            $delete_text = $this->remove_newlines($this->moblog_array['moblog_ignore_text'], $this->newline);
            $delete_array = explode($this->newline, $delete_text);

            if (count($delete_array) > 0) {
                foreach ($delete_array as $ignore) {
                    if (trim($ignore) != '') {
                        $this->body = str_replace(trim($ignore), '', $this->body);
                    }
                }
            }
        }

        /** -------------------------------------
        /**  Specified Fields for Email Text
        /** -------------------------------------*/
        if (preg_match_all("/[\<\{]field\:(.*?)[\}\>](.*?)[\<\{]\/field\:(.*?)[\}\>]/", $this->body, $matches)) {
            ee()->db->select('channel_fields.field_id, channel_fields.field_name, channel_fields.field_label, channel_fields.field_fmt');
            ee()->db->from('channel_fields');

            /* -------------------------------------
            /*  Hidden Configuration Variable
            /*  - moblog_allow_nontextareas => Removes the textarea only restriction
            /*	for custom fields in the moblog module (y/n)
            /* -------------------------------------*/
            if (ee()->config->item('moblog_allow_nontextareas') != 'y') {
                ee()->db->where('channel_fields.field_type', 'textarea');
            }

            $results = ee()->db->get();

            if ($results->num_rows() > 0) {
                $field_name = array();
                $field_label = array();
                $field_format = array();

                foreach ($results->result_array() as $row) {
                    $field_name[$row['field_id']] = $row['field_name'];
                    $field_label[$row['field_id']] = $row['field_label'];
                    $field_format[$row['field_id']] = $row['field_fmt'];
                }

                unset($results);

                for ($i = 0; $i < count($matches[0]); $i++) {
                    $x = preg_split("/[\s]+/", $matches['1'][$i]);

                    if ($key = array_search($x['0'], $field_name) or $key = array_search($x['0'], $field_label)) {
                        $format = (! isset($x['1']) or ! stristr($x['1'], "format")) ? $field_format[$key] : preg_replace("/format\=[\"\'](.*?)[\'\"]/", "$1", trim($x['1']));

                        $matches['2'][$i] = str_replace($this->newline, "\n", $matches['2'][$i]);

                        if (! isset($this->entry_data[$key])) {
                            $this->entry_data[$key] = array('data' => $matches['2'][$i],
                                'format' => $format);
                        } else {
                            $this->entry_data[$key] = array('data' => $matches['2'][$i] . $this->entry_data[$key]['data'],
                                'format' => $format);
                        }

                        $this->body = str_replace($matches['0'][$i], '', $this->body);
                    }
                }
            }
        }

        // Return New Lines

        $this->body = str_replace($this->newline, "\n", $this->body);

        // Parse template

        $tag = 'field';

        if (! preg_match_all("/" . LD . $tag . "(.*?)" . RD . "(.*?)" . LD . '\/' . $tag . RD . "/s", $this->template, $matches)) {
            $this->parse_field($this->moblog_array['moblog_field_id'], $this->template);
        } else {
            for ($i = 0; $i < count($matches['0']) ; $i++) {
                $params = ee('Variables/Parser')->parseTagParameters($matches['1'][$i]);

                $params['format'] = (! isset($params['format'])) ? '' : $params['format'];
                $params['name'] = (! isset($params['name'])) ? '' : $params['name'];

                $this->parse_field($params, $matches['2'][$i]);
                $this->template = str_replace($matches['0'], '', $this->template);
            }

            if (trim($this->template) != '') {
                $this->parse_field($this->moblog_array['moblog_field_id'], $this->template);
            }
        }

        // Prep entry data

        if (count($this->entry_data) > 0) {
            foreach ($this->entry_data as $key => $value) {
                // ----------------------------------------
                //  Put this in here in case some one has
                //  {field:body}{/field:body} in their email
                //  and yet has their default field set to none
                // ----------------------------------------

                if ($key == 'none') {
                    continue;
                }

                // Load the text helper
                ee()->load->helper('text');

                $combined_data = $value['data'];
                $combined_data = (ee()->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities(trim($combined_data)) : trim($combined_data);

                $data['field_id_' . $key] = $combined_data;
                $data['field_ft_' . $key] = $value['format'];
            }
        }

        $entry->set($data);

        $data['category'] = array();

        if ($this->post_data['categories'] == 'all') {
            $cat_groups = explode('|', $query->row('cat_group'));
            ee()->load->model('category_model');

            foreach ($cat_groups as $cat_group_id) {
                $cats_q = ee()->category_model->get_channel_categories($cat_group_id);

                if ($cats_q->num_rows() > 0) {
                    foreach ($cats_q->result() as $row) {
                        $data['category'][] = $row->cat_id;
                    }
                }
            }

            $data['category'] = array_unique($data['category']);
        } elseif ($this->post_data['categories'] != 'none') {
            $data['category'] = explode('|', $this->post_data['categories']);
            $data['category'] = array_unique($data['category']);
        }

        $entry->Categories->set($data['category']);

        // forgive me, please.

        // ...

        // ...

        // No.  I don't think I will forgive you.
        $orig_group_id = ee()->session->userdata('group_id');
        $orig_can_assign = ee()->session->userdata('can_assign_post_authors');
        $orig_can_edit = ee()->session->userdata('can_edit_other_entries');
        ee()->session->userdata['group_id'] = 1;
        ee()->session->userdata['can_assign_post_authors'] = 'y';
        ee()->session->userdata['can_edit_other_entries'] = 'y';

        // Insert the Entry

        // Max URL title length, minus uniqid length, minus separator
        $url_title = substr(ee('Format')->make('Text', $data['title'])->urlSlug()->compile(), 0, URL_TITLE_MAX_LENGTH - 23 - 1);

        $separator = (ee()->config->item('word_separator') == 'dash') ? '-' : '_';

        $entry->url_title = uniqid($url_title . $separator, true);

        $result = $entry->save();

        if ($result) {
            $this->entries_added++;
        }

        ee()->session->userdata['can_assign_post_authors'] = $orig_can_assign;
        ee()->session->userdata['group_id'] = $orig_group_id;
        ee()->session->userdata['can_edit_other_entries'] = $orig_can_edit;
    }

    /**
     * 	Assign Params
     *
     *	Creates an associative array from a string
     *	of parameters: sort="asc" limit="2" etc.
     *
     * 	Return parameters as an array - Use TMPL one eventually
     *
     *	@param string
     */
    public function assign_parameters($str)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('4.0', "ee('Variables/Parser')->parseTagParameters()");

        return ee('Variables/Parser')->parseTagParameters($str);
    }

    /**
     * 	parse_field
     *
     *	@param mixed - params
     * 	@param
     *	@param string
     */
    public function parse_field($params, $field_data)
    {
        $field_id = '1';
        $format = 'none';

        /** -----------------------------
        /**  Determine Field Id and Format
        /** -----------------------------*/
        if (! is_array($params)) {
            $field_id = $params;

            ee()->db->select('field_fmt');
            ee()->db->where('field_id', $field_id);
            $results = ee()->db->get('channel_fields');

            $format = ($results->num_rows() > 0) ? $results->row('field_fmt') : 'none';
        } else {
            if ($params['name'] != '' && $params['format'] == '') {
                $xsql = (ee()->config->item('moblog_allow_nontextareas') == 'y') ? "" : " AND exp_channel_fields.field_type = 'textarea' ";

                ee()->db->select('field_id, field_fmt');
                ee()->db->where('group_id', $field_id);
                ee()->db->where('(field_name = "' . $params['name'] . '" OR field_label = "' . $params['name'] . '")', null, false);

                if (ee()->config->item('moblog_allow_nontextareas') != 'y') {
                    ee()->db->where('field_type', 'textarea');
                }

                $results = ee()->db->get('channel_fields');

                $field_id = ($results->num_rows() > 0) ? $results->row('field_id') : $this->moblog_array['moblog_field_id'];
                $format = ($results->num_rows() > 0) ? $results->row('field_fmt') : 'none';
            } elseif ($params['name'] == '' && $params['format'] == '') {
                $field_id = $this->moblog_array['moblog_field_id'];

                ee()->db->select('field_fmt');
                ee()->db->where('field_id', $field_id);

                $results = ee()->db->get('channel_fields');

                $format = $results->row('field_fmt') ;
            } elseif ($params['name'] == '' && $params['format'] != '') {
                $field_id = $this->moblog_array['moblog_field_id'];
                $format = $params['format'];
            } elseif ($params['name'] != '' && $params['format'] != '') {
                $xsql = (ee()->config->item('moblog_allow_nontextareas') == 'y') ? "" : " AND exp_channel_fields.field_type = 'textarea' ";

                ee()->db->select('field_id');
                ee()->db->where('(field_name = "' . $params['name'] . '" OR field_label = "' . $params['name'] . '")');

                if (ee()->config->item('moblog_allow_nontextareas') != 'y') {
                    ee()->db->where('field_type', 'textarea');
                }

                $results = ee()->db->get('channel_fields');

                $field_id = ($results->num_rows() > 0) ? $results->row('field_id') : $this->moblog_array['moblog_field_id'];
                $format = $params['format'];
            }
        }

        $dir_id = $this->moblog_array['moblog_upload_directory'];

        ee()->load->model('file_model');
        ee()->load->model('file_upload_preferences_model');

        $prefs_q = ee()->file_upload_preferences_model->get_file_upload_preferences(1, $dir_id);
        $sizes_q = ee()->file_model->get_dimensions_by_dir_id($dir_id);

        $dir_server_path = (isset($prefs_q['server_path'])) ? $prefs_q['server_path'] : '';

        // @todo if 0 skip!!
        $thumb_data = array();
        $image_data = array();

        foreach ($sizes_q->result() as $row) {
            foreach (array('thumb', 'image') as $which) {
                if ($row->id == $this->moblog_array['moblog_' . $which . '_size']) {
                    ${$which . '_data'} = array(
                        'dir' => '_' . $row->short_name . '/',
                        'height' => $row->height,
                        'width' => $row->width
                    );
                }
            }
        }

        /** -----------------------------
        /**  Parse Content
        /** -----------------------------*/
        $pair_array = array('images','audio','movie','files');
        $float_data = $this->post_data;
        $params = array();

        foreach ($pair_array as $type) {
            if (! preg_match_all("/" . LD . $type . "(.*?)" . RD . "(.*?)" . LD . '\/' . $type . RD . "/s", $field_data, $matches)) {
                continue;
            }

            if (count($matches['0']) == 0) {
                continue;
            }

            for ($i = 0; $i < count($matches['0']) ; $i++) {
                $template_data = '';

                if ($type != 'files' && (! isset($float_data[$type]) or count($float_data[$type]) == 0)) {
                    $field_data = str_replace($matches['0'][$i], '', $field_data);

                    continue;
                }

                // Assign parameters, if any
                if (isset($matches['1'][$i]) && trim($matches['1'][$i]) != '') {
                    $params = ee('Variables/Parser')->parseTagParameters(trim($matches['1'][$i]));
                }

                $params['match'] = (! isset($params['match'])) ? '' : $params['match'];

                /** ----------------------------
                /**  Parse Pairs
                /** ----------------------------*/

                // Files is a bit special.  It goes last and will clear out remaining files.  Has match parameter
                if ($type == 'files' && $params['match'] != '') {
                    if (! count($float_data)) {
                        break;
                    }

                    foreach ($float_data as $ftype => $value) {
                        if (! in_array($ftype, $pair_array) or ! ($params['match'] == 'all' or stristr($params['match'], $ftype))) {
                            continue;
                        }

                        foreach ($float_data[$ftype] as $k => $file) {
                            // not an image
                            if ($ftype != 'images') {
                                $template_data .= str_replace('{file}', $this->upload_dir_code . $file, $matches['2'][$i]);

                                continue;
                            }
                            // most definitely an image

                            // Figure out sizes
                            $file_rel_path = empty($image_data) ? $file : $image_data['dir'] . $file;
                            $file_dimensions = @getimagesize($dir_server_path . $file_rel_path);
                            $filename = $this->upload_dir_code . $file_rel_path;

                            $thumb_replace = '';
                            $thumb_dimensions = false;

                            if (! empty($thumb_data)) {
                                $thumb_rel_path = $thumb_data['dir'] . $file;
                                $thumb_replace = $this->upload_dir_code . $thumb_rel_path;
                                $thumb_dimensions = @getimagesize($dir_server_path . $thumb_rel_path);
                            }

                            $details = array(
                                'width' => $file_dimensions ? $file_dimensions[0] : '',
                                'height' => $file_dimensions ? $file_dimensions[1] : '',
                                'thumbnail' => $thumb_replace,
                                'thumb_width' => $thumb_dimensions ? $thumb_dimensions[0] : '',
                                'thumb_height' => $thumb_dimensions ? $thumb_dimensions[1] : ''
                            );

                            $temp_data = str_replace('{file}', $filename, $matches['2'][$i]);

                            foreach ($details as $d => $dv) {
                                $temp_data = str_replace('{' . $d . '}', $dv, $temp_data);
                            }

                            $template_data .= $temp_data;
                        }
                    }
                } elseif (isset($float_data[$type])) {
                    foreach ($float_data[$type] as $k => $file) {
                        if ($type != 'images') {
                            $template_data .= str_replace('{file}', $this->upload_dir_code . $file, $matches['2'][$i]);

                            continue;
                        }

                        // It's an image, work out sizes
                        // Figure out sizes
                        $file_rel_path = empty($image_data) ? $file : $image_data['dir'] . $file;
                        $file_dimensions = @getimagesize($dir_server_path . $file_rel_path);
                        $filename = $this->upload_dir_code . $file_rel_path;

                        $thumb_replace = '';
                        $thumb_dimensions = false;

                        if (! empty($thumb_data)) {
                            $thumb_rel_path = $thumb_data['dir'] . $file;
                            $thumb_replace = $this->upload_dir_code . $thumb_rel_path;
                            $thumb_dimensions = @getimagesize($dir_server_path . $thumb_rel_path);
                        }

                        $details = array(
                            'width' => $file_dimensions ? $file_dimensions[0] : '',
                            'height' => $file_dimensions ? $file_dimensions[1] : '',
                            'thumbnail' => $thumb_replace,
                            'thumb_width' => $thumb_dimensions ? $thumb_dimensions[0] : '',
                            'thumb_height' => $thumb_dimensions ? $thumb_dimensions[1] : ''
                        );

                        $temp_data = str_replace('{file}', $filename, $matches['2'][$i]);

                        foreach ($details as $d => $dv) {
                            $temp_data = str_replace('{' . $d . '}', $dv, $temp_data);
                        }

                        $template_data .= $temp_data;
                    }
                }

                // Replace tag pair with template data
                $field_data = str_replace($matches['0'][$i], $template_data, $field_data);

                // Unset member of float data array
                if (isset($float_data[$type]) && count($float_data[$type]) == 0) {
                    unset($float_data[$type]);
                }
            }
        }

        /** ------------------------------
        /**  Variable Single:  text
        /** ------------------------------*/
        $field_data = str_replace(array('{text}', '{sender_email}'), array($this->body, $this->sender_email), $field_data);
        $this->entry_data[$field_id]['data'] = (! isset($this->entry_data[$field_id])) ? $field_data : $this->entry_data[$field_id]['data'] . "\n" . $field_data;
        $this->entry_data[$field_id]['format'] = $format;
    }

    /**
     * 	Parse Email
     *
     *	@param mixed - Email Data
     * 	@param
     */
    public function parse_email($email_data, $type = 'norm')
    {
        ee()->load->library('filemanager');

        $boundary = ($type != 'norm') ? $this->multi_boundary : $this->boundary;
        $email_data = str_replace('boundary=' . substr($boundary, 2), 'BOUNDARY_HERE', $email_data);

        $email_parts = explode($boundary, $email_data);

        if (count($email_parts) < 2) {
            $boundary = str_replace("+", "\+", $boundary);
            $email_parts = explode($boundary, $email_data);
        }

        if (count($email_parts) < 2) {
            return false;
            unset($email_parts);
            unset($email_data);
        }

        $upload_dir_id = $this->moblog_array['moblog_upload_directory'];

        if ($upload_dir_id != 0) {
            $this->upload_dir_code = '{filedir_' . $upload_dir_id . '}';
        }

        //  Find Attachments
        foreach ($email_parts as $key => $value) {
            // Skip headers and those with no content-type
            if ($key == '0' or stristr($value, 'Content-Type:') === false) {
                continue;
            }

            $contents = $this->find_data($value, "Content-Type:", $this->newline);
            $x = explode(';', $contents);
            $content_type = $x['0'];

            $content_type = strtolower($content_type);
            $pieces = explode('/', trim($content_type));
            $type = trim($pieces['0']);
            $subtype = (! isset($pieces['1'])) ? '0' : trim($pieces['1']);

            $charset = 'auto';

            /** --------------------------
            /**  Outlook Exception
            /** --------------------------*/
            if ($type == 'multipart' && $subtype != 'appledouble') {
                if (! stristr($value, 'boundary=')) {
                    continue;
                }

                $this->multi_boundary = "--" . $this->find_data($value, "boundary=", $this->newline);
                $this->multi_boundary = trim(str_replace('"', '', $this->multi_boundary));

                if (strlen($this->multi_boundary) == 0) {
                    continue;
                }

                $this->parse_email($value, 'multi');
                $this->multi_boundary = '';

                continue;
            }

            /** --------------------------
            /**  Quick Grab of Headers
            /** --------------------------*/
            $headers = $this->find_data($value, '', $this->newline . $this->newline);

            /** ---------------------------
            /**  Text : plain, html, rtf
            /** ---------------------------*/
            if ($type == 'text' && $headers != '' &&
                (($this->txt_override === true && $subtype == 'plain') or ! stristr($headers, 'name='))) {
                $duo = $this->newline . $this->newline;
                $text = $this->find_data($value, $duo, '');

                if ($text == '') {
                    $text = $this->find_data($value, $this->newline, '');
                }

                /** ------------------------------------
                /**  Charset Available?
                /** ------------------------------------*/
                if (preg_match("/charset=(.*?)(\s|" . $this->newline . ")/is", $headers, $match)) {
                    $charset = trim(str_replace(array("'", '"', ';'), '', $match['1']));
                }

                /** ------------------------------------
                /**  Check for Encoding of Text
                /** ------------------------------------*/
                if (stristr($value, 'Content-Transfer-Encoding')) {
                    $encoding = $this->find_data($value, "Content-Transfer-Encoding:", $this->newline);

                    /** ------------------------------------
                    /**  Check for Quoted-Printable encoding
                    /** ------------------------------------*/
                    if (stristr($encoding, "quoted-printable")) {
                        $text = str_replace($this->newline, "\n", $text);
                        $text = quoted_printable_decode($text);
                        $text = (substr($text, 0, 1) != '=') ? $text : substr($text, 1);
                        $text = (substr($text, -1) != '=') ? $text : substr($text, 0, -1);
                        $text = $this->remove_newlines($text, $this->newline);
                    }

                    /** ------------------------------------
                    /**  Check for Base 64 encoding:  MIME
                    /** ------------------------------------*/
                    elseif (stristr($encoding, "base64")) {
                        $text = str_replace($this->newline, "\n", $text);
                        $text = base64_decode(trim($text));
                        $text = $this->remove_newlines($text, $this->newline);
                    }
                }

                /** ----------------------------------
                /**  T-Mobile - In cyberspace, no one can hear you cream.
                /** ----------------------------------*/
                if (trim($text) != '' && stristr($text, 'This message was sent from a T-Mobile wireless phone') !== false) {
                    $text = '';
                }

                if ($this->charset != ee()->config->item('charset')) {
                    if (function_exists('mb_convert_encoding')) {
                        $text = mb_convert_encoding($text, strtoupper(ee()->config->item('charset')), strtoupper($this->charset));
                    } elseif (function_exists('iconv') and ($iconvstr = @iconv(strtoupper($this->charset), strtoupper(ee()->config->item('charset')), $text)) !== false) {
                        $text = $iconvstr;
                    } elseif (strtolower(ee()->config->item('charset')) == 'utf-8' && strtolower($this->charset) == 'iso-8859-1') {
                        $text = utf8_encode($text);
                    } elseif (strtolower(ee()->config->item('charset')) == 'iso-8859-1' && strtolower($this->charset) == 'utf-8') {
                        $text = utf8_decode($text);
                    }
                }

                // RTF and HTML are considered alternative text
                $subtype = ($subtype != 'html' && $subtype != 'rtf') ? 'plain' : 'alt';

                // Same content type, then join together
                $this->post_data[$type][$subtype] = (isset($this->post_data[$type][$subtype])) ? $this->post_data[$type][$subtype] . " $text" : $text;

                // Plain text takes priority for body data.
                $this->body = (! isset($this->post_data[$type]['plain'])) ? $this->post_data[$type]['alt'] : $this->post_data[$type]['plain'];
            } elseif ($type == 'image' or $type == 'application' or $type == 'audio' or $type == 'video' or $subtype == 'appledouble' or $type == 'text') { // image or application
                if ($this->_process_attachment($value, $type, $subtype) === false) {
                    return false;
                }
            } // End files/images section
        } // End foreach

        return true;
    }

    /**
     * Parses out file data and saves it to moblog upload directory
     *
     * @param	string	$value		Raw email data
     * @param	string	$type		Type of content (first half of MIME)
     * @param	string	$subtype	Subtype type of content (second half of MIME)
     * @return	boolean	Success or failure
     */
    private function _process_attachment($value, $type, $subtype)
    {
        $upload_dir_id = $this->moblog_array['moblog_upload_directory'];

        if ($upload_dir_id != 0) {
            $this->upload_dir_code = '{filedir_' . $upload_dir_id . '}';
        }

        // no upload directory?  skip

        if ($upload_dir_id == 0) {
            return true;
        }

        if ($subtype == 'appledouble') {
            if (! $data = $this->appledouble($value)) {
                return true;
            } else {
                $value = $data['value'];
                $subtype = $data['subtype'];
                $type = $data['type'];
                unset($data);
            }
        }

        /** ------------------------------
        /**  Determine Filename
        /** ------------------------------*/
        $contents = $this->find_data($value, "name=", $this->newline);

        if ($contents == '') {
            $contents = $this->find_data($value, 'Content-Location: ', $this->newline);
        }

        if ($contents == '') {
            $contents = $this->find_data($value, 'Content-ID: ', $this->newline);
            $contents = str_replace('<', '', $contents);
            $contents = str_replace('<', '', $contents);
        }

        $x = explode(';', trim($contents));
        $filename = ($x['0'] == '') ? 'moblogfile' : $x['0'];

        $filename = trim(str_replace('"', '', $filename));
        $filename = str_replace($this->newline, '', $filename);

        if (stristr($filename, 'dottedline') or stristr($filename, 'spacer.gif') or stristr($filename, 'masthead.jpg')) {
            return true;
        }

        /** --------------------------------
        /**  File/Image Code and Cleanup
        /** --------------------------------*/
        $duo = $this->newline . $this->newline;
        $file_code = $this->find_data($value, $duo, '');

        if ($file_code == '') {
            $file_code = $this->find_data($value, $this->newline, '');

            if ($file_code == '') {
                $this->message_array = 'invalid_file_data';

                return false;
            }
        }

        /** --------------------------------
        /**  Determine Encoding
        /** --------------------------------*/
        $contents = $this->find_data($value, "Content-Transfer-Encoding: ", $this->newline);
        $x = explode(';', $contents);
        $encoding = $x['0'];
        $encoding = trim(str_replace('"', '', $encoding));
        $encoding = str_replace($this->newline, '', $encoding);

        if (! stristr($encoding, "base64") && ! stristr($encoding, "7bit") && ! stristr($encoding, "8bit") && ! stristr($encoding, "quoted-printable")) {
            if ($type == 'text') {
                // RTF and HTML are considered alternative text
                $subtype = ($subtype != 'html' && $subtype != 'rtf') ? 'plain' : 'alt';

                // Same content type, then join together
                $this->post_data[$type][$subtype] = (isset($this->post_data[$type][$subtype])) ? $this->post_data[$type][$subtype] . ' ' . $file_code : $file_code;

                // Plain text takes priority for body data.
                $this->body = (! isset($this->post_data[$type]['plain'])) ? $this->post_data[$type]['alt'] : $this->post_data[$type]['plain'];
            }

            return true;
        }

        // Eudora and Mail.app use this by default
        if (stristr($encoding, "quoted-printable")) {
            $file_code = quoted_printable_decode($file_code);
        }

        // Base64 gets no space and no line breaks
        $replace = (! stristr($encoding, "base64")) ? "\n" : '';
        $file_code = trim(str_replace($this->newline, $replace, $file_code));

        // PHP function sometimes misses opening and closing equal signs
        if (stristr($encoding, "quoted-printable")) {
            $file_code = (substr($file_code, 0, 1) != '=') ? $file_code : substr($file_code, 1);
            $file_code = (substr($file_code, -1) != '=') ? $file_code : substr($file_code, 0, -1);
        }

        // Decode so that we can run xss clean on the raw
        // data once we've determined the file type

        if (stristr($encoding, "base64")) {
            $file_code = base64_decode($file_code);
        }

        /** ------------------------------
        /**  Check and adjust for multiple files with same file name
        /** ------------------------------*/
        ee()->load->library('filemanager');

        $file_path = ee()->filemanager->clean_filename(
            $filename,
            $upload_dir_id,
            array('ignore_dupes' => false)
        );
        $filename = basename($file_path);

        /** ---------------------------
        /**  Put Info in Post Data array
        /** ---------------------------*/
        $ext = trim(strrchr($filename, '.'), '.');
        $is_image = false; // This is needed for XSS cleaning

        if (in_array(strtolower($ext), $this->movie)) { // Movies
            $this->post_data['movie'][] = $filename;
        } elseif (in_array(strtolower($ext), $this->audio)) { // Audio
            $this->post_data['audio'][] = $filename;
        } elseif (in_array(strtolower($ext), $this->image)) { // Images
            $this->post_data['images'][] = $filename;

            $key = count($this->post_data['images']) - 1;

            $type = 'image'; // For those crazy application/octet-stream images

            $is_image = true;
        } elseif (in_array(strtolower($ext), $this->files)) { // Files
            $this->post_data['files'][] = $filename;
        } else {
            return true;
        }

        // Clean the file
        ee()->load->helper('xss');

        if (xss_check()) {
            $xss_result = ee('Security/XSS')->clean($file_code, $is_image);

            // XSS Clean Failed - bail out
            if ($xss_result === false) {
                $this->message_array[] = 'error_writing_attachment';

                return false;
            }

            if (! $is_image) {
                $file_code = $xss_result;
            }
        }

        // AT&T phones send the message as a .txt file
        // This checks to see if this email is from an AT&T phone,
        // not an encoded file, and has a .txt file extension in the filename

        if ($this->attach_as_txt === true && ! stristr($encoding, "base64")) {
            if ($ext == 'txt' && preg_match("/Content-Disposition:\s*inline/i", $headers, $found)) {
                $this->attach_text = $file_code;
                $this->attach_name = $filename;

                return true; // No upload of file.
            }
        }

        // Check to see if we're dealing with relative paths
        if (strncmp($file_path, '..', 2) == 0) {
            $directory = dirname($file_path);
            $file_path = realpath(substr($directory, 1)) . '/' . $filename;
        }

        // Upload the file
        $config = array('upload_path' => dirname($file_path));
        $mime = $type . '/' . $subtype;
        ee()->load->library('upload', $config);

        if (ee()->upload->raw_upload($filename, $file_code) === false) {
            $this->message_array[] = ee()->upload->display_errors();
            $this->message_array[] = 'error_writing_attachment';

            return false;
        }

        // Disable xss cleaning in the filemanager
        ee()->filemanager->xss_clean_off();

        // Send the file
        $result = ee()->filemanager->save_file(
            $file_path,
            $upload_dir_id,
            array(
                'title' => $filename,
                'file_name' => $filename
            )
        );

        unset($file_code);

        // Check to see the result
        if ($result['status'] === false) {
            // $result['message']
            $this->message_array[] = 'error_writing_attachment';
            $this->message_array[] = print_r($result, true);

            return false;
        }

        $this->email_files[] = $filename;
        $this->uploads++;

        return true;
    }

    /**
     * 	Strip Apple Double Crap
     *
     *	@param string
     */
    public function appledouble($data)
    {
        if (stristr($data, 'boundary=') === false) {
            return false;
        }

        $boundary = "--" . $this->find_data($data, "boundary=", $this->newline);
        $boundary = trim(str_replace('"', '', $boundary));
        $boundary = str_replace("+", "\+", $boundary);
        $email_parts = explode($boundary, $data);

        if (count($email_parts) < 2) {
            return false;
        }

        foreach ($email_parts as $value) {
            $content_type = $this->find_data($value, "Content-Type:", ";");
            $pieces = explode('/', trim($content_type));
            $type = trim($pieces['0']);
            $subtype = (! isset($pieces['1'])) ? '0' : trim($pieces['1']);

            if ($type == 'image' or $type == 'audio' or $type == 'video') {
                $data = array('value' => $value,
                    'type' => $type,
                    'subtype' => $subtype);

                return $data;
            }
        }

        return false;
    }

    /**
     * 	Check Login
     */
    public function check_login()
    {
        $this->body = trim($this->body);
        $login = $this->find_data($this->body, '', $this->newline);

        if ($login == '' or ! stristr($login, ':')) {
            $login = $this->find_data($this->body, 'AUTH:', $this->newline);
        }

        if ($login == '' or ! stristr($login, ':')) {
            return false;
        }

        $x = explode(":", $login);

        $username = (isset($x['1']) && $x['0'] == 'AUTH') ? $x['1'] : $x['0'];
        $password = (isset($x['2']) && $x['0'] == 'AUTH') ? $x['2'] : $x['1'];

        //  Check Username and Password, First
        ee()->load->library('auth');

        if (false == ($auth = ee()->auth->authenticate_username($username, $password))) {
            return false;
        }

        $assigned_channels = ee()->session->getMember()->getAssignedChannels()->indexBy('channel_id');

        if (! isset($assigned_channels[$this->moblog_array['moblog_channel_id']])) {
            return false;
        }

        $this->author = $auth->member('member_id');
        $this->body = str_replace($login, '', $this->body);

        return true;
    }

    /**
     * 	Find Boundary
     */
    public function find_boundary($email_data)
    {
        if (stristr($email_data, 'boundary=') === false) {
            return false;
        } else {
            $this->boundary = "--" . $this->find_data($email_data, "boundary=", $this->newline);
            $x = explode(';', $this->boundary);
            $this->boundary = trim(str_replace('"', '', $x['0']));

            return true;
        }
    }

    /**
     * 	Pop Command.
     *
     * 	Send pop command to the server.
     *
     *	@param string
     *	@return string
     */
    public function pop_command($cmd = "")
    {
        if (! $this->fp) {
            return false;
        }

        if ($cmd != "") {
            fwrite($this->fp, $cmd . $this->pop_newline);
        }

        $line = $this->remove_newlines(fgets($this->fp, 1024));

        return $line;
    }

    /**
     * 	Remove New Lines
     *
     *	@param string
     *	@param	string
     *	@return string
     */
    public function remove_newlines($str, $replace = '')
    {
        if (strpos($str, "\r") !== false or strpos($str, "\n") !== false) {
            $str = str_replace(array("\r\n", "\r", "\n"), $replace, $str);
        }

        return $str;
    }

    /**
     * 	ISO Clean
     *
     *	@param string
     *	@return string
     */
    public function iso_clean($str)
    {
        if (stristr($str, '=?') === false) {
            return $str;
        }

        // -------------------------------------------------
        //  There exists two functions that do this for us
        //  but they are not available on all servers and some
        //  seem to work better than others I have found. The
        //  base64_decode() method works for many encodings
        //  but I am not sure how well it handles non Latin
        //  characters.
        //
        //  The mb_decode_mimeheader() function seems to trim
        //  any line breaks off the end of the str, so we put
        //  those back because we need it for the Header
        //  matching stuff.  I added it on for the imap_utf8()
        //  function just in case.
        // -------------------------------------------------

        if (function_exists('imap_utf8') && strtoupper(ee()->config->item('charset')) == 'UTF-8') {
            return rtrim(imap_utf8($str)) . "\r\n";
        }

        if (function_exists('mb_decode_mimeheader')) {
            // mb_decode_mimeheader() doesn't replace underscores
            return str_replace('_', ' ', rtrim(mb_decode_mimeheader($str))) . "\r\n";
        }

        if (function_exists('iconv_mime_decode')) {
            return rtrim(iconv_mime_decode($str)) . "\r\n";
        }

        if (substr(trim($str), -2) != '?=') {
            $str = trim($str) . '?=';
        }

        if (preg_match("|\=\?iso\-(.*?)\?[A-Z]{1}\?(.*?)\?\=|i", trim($str), $mime)) {
            if ($mime['1'] == '8859-1') {
                $charHex = array('0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F');

                for ($z = 0, $sz = count($charHex); $z < $sz; ++$z) {
                    for ($i = 0, $si = count($charHex); $i < $si; ++$i) {
                        $mime['2'] = str_replace('=' . $charHex[$z] . $charHex[$i], chr(hexdec($charHex[$z] . $charHex[$i])), $mime['2']);
                    }
                }

                $str = str_replace($mime['0'], $mime['2'], $str);
            } else {
                $str = str_replace($mime['0'], base64_decode($mime['2']), $str);
            }

            $str = str_replace('_', ' ', $str);
        }

        return ltrim($str);
    }

    /**
     * 	Find Data
     *
     *	@param	string
     * 	@param	string
     *	@param 	string
     *	@return string
     */
    public function find_data($str, $begin, $end)
    {
        $new = '';

        if ($begin == '') {
            $p1 = 0;
        } else {
            if (strpos(strtolower($str), strtolower($begin)) === false) {
                return $new;
            }

            $p1 = strpos(strtolower($str), strtolower($begin)) + strlen($begin);
        }

        if ($end == '') {
            $p2 = strlen($str);
        } else {
            if (strpos(strtolower($str), strtolower($end), $p1) === false) {
                return $new;
            }

            $p2 = strpos(strtolower($str), strtolower($end), $p1);
        }

        $new = substr($str, $p1, ($p2 - $p1));

        return $new;
    }
}
// END CLASS

// EOF
