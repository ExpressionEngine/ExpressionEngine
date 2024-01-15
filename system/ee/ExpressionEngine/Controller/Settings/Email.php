<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Settings;

use CP_Controller;

/**
 * Outgoing Email Settings Controller
 */
class Email extends Settings
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_comm')) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * General Settings
     */
    public function index()
    {
        $tls_options = array(
            STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT => '1.0',
            STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT => '1.1',
            STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT => '1.2'
        );
        if (version_compare(PHP_VERSION, 7.4, '>=')) {
            $tls_options[STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT] = '1.3';
        }
        if (ee()->config->item('tls_crypto_method') !== false && ! array_key_exists(ee()->config->item('tls_crypto_method'), $tls_options)) {
            $tls_options[ee()->config->item('tls_crypto_method')] = ee()->config->item('tls_crypto_method'); //support custom value
        }
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'webmaster_email',
                    'desc' => 'webmaster_email_desc',
                    'fields' => array(
                        'webmaster_email' => array('type' => 'text', 'required' => true),
                    )
                ),
                array(
                    'title' => 'webmaster_name',
                    'desc' => 'webmaster_name_desc',
                    'fields' => array(
                        'webmaster_name' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'email_charset',
                    'desc' => 'email_charset_desc',
                    'fields' => array(
                        'email_charset' => array('type' => 'text')
                    )
                ),
                array(
                    'title' => 'mail_protocol',
                    'desc' => 'mail_protocol_desc',
                    'fields' => array(
                        'mail_protocol' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'mail' => lang('php_mail'),
                                'sendmail' => lang('sendmail'),
                                'smtp' => lang('smtp')
                            ),
                            'group_toggle' => array(
                                'smtp' => 'smtp_options'
                            )
                        )
                    )
                ),
                array(
                    'title' => 'email_newline',
                    'desc' => 'email_newline_desc',
                    'fields' => array(
                        'email_newline' => array(
                            'type' => 'radio',
                            'choices' => array(
                                '\n' => '\\\n',
                                '\r\n' => '\\\r\\\n',
                                '\r' => '\\\r'
                            ),
                            // email_newline is converted to double-quoted representation on load
                            'value' => ee()->config->item('email_newline_form_safe')
                        )
                    )
                )
            ),
            'smtp_options' => array(
                'group' => 'smtp_options',
                'settings' => array(
                    array(
                        'title' => 'smtp_server',
                        'desc' => 'smtp_server_desc',
                        'fields' => array(
                            'smtp_server' => array('type' => 'text')
                        )
                    ),
                    array(
                        'title' => 'smtp_port',
                        'fields' => array(
                            'smtp_port' => array('type' => 'text')
                        )
                    ),
                    array(
                        'title' => 'username',
                        'fields' => array(
                            'smtp_username' => array('type' => 'text')
                        )
                    ),
                    array(
                        'title' => 'password',
                        'fields' => array(
                            'smtp_password' => array('type' => 'password')
                        )
                    ),
                    array(
                        'title' => 'email_smtp_crypto',
                        'desc' => 'email_smtp_crypto_desc',
                        'fields' => array(
                            'email_smtp_crypto' => array(
                                'type' => 'radio',
                                'choices' => array(
                                    'ssl' => lang('ssl'),
                                    'tls' => lang('tls'),
                                    '' => lang('unencrypted')
                                ),
                                'group_toggle' => array(
                                    'tls' => 'tls_options'
                                )
                            )
                        )
                    ),
                    array(
                        'title' => 'tls_version',
                        'desc' => 'tls_version_desc',
                        'group' => 'tls_options',
                        'fields' => array(
                            'tls_crypto_method' => array(
                                'type' => 'radio',
                                'choices' => $tls_options,
                                'value' => (ee()->config->item('tls_crypto_method') !== false) ? ee()->config->item('tls_crypto_method') : STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
                            )
                        )
                    ),
                )
            ),
            'sending_options' => array(
                array(
                    'title' => 'mail_format',
                    'desc' => 'mail_format_desc',
                    'fields' => array(
                        'mail_format' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'plain' => lang('plain_text'),
                                'html' => lang('html')
                            )
                        )
                    )
                ),
                array(
                    'title' => 'word_wrap',
                    'desc' => 'word_wrap_desc',
                    'fields' => array(
                        'word_wrap' => array('type' => 'yes_no')
                    )
                ),
            )
        );

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'webmaster_email',
                'label' => 'lang:webmaster_email',
                'rules' => 'required|valid_email'
            ),
            array(
                'field' => 'webmaster_name',
                'label' => 'lang:webmaster_name',
                'rules' => 'strip_tags|valid_xss_check'
            ),
            array(
                'field' => 'smtp_server',
                'label' => 'lang:smtp_server',
                'rules' => 'callback__smtp_required_field'
            ),
            array(
                'field' => 'smtp_port',
                'label' => 'lang:smtp_port',
                'rules' => 'is_natural_no_zero'
            )
        ));

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        $base_url = ee('CP/URL')->make('settings/email');

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            if ($this->saveSettings($vars['sections'])) {
                ee()->view->set_message('success', lang('preferences_updated'), lang('preferences_updated_desc'), true);
            }

            ee()->functions->redirect($base_url);
        } elseif (ee()->form_validation->errors_exist()) {
            ee()->view->set_message('issue', lang('settings_save_error'), lang('settings_save_error_desc'));
        }

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
        ));

        ee()->view->base_url = $base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('outgoing_email');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';
        ee()->view->cp_breadcrumbs = array(
            '' => lang('email_settings')
        );
        ee()->cp->render('settings/form', $vars);
    }

    /**
     * A validation callback for required email configuration strings only
     * if SMTP is the selected protocol method
     *
     * @access	public
     * @param	string	$str	the string being validated
     * @return	boolean	Whether or not the string passed validation
     **/
    public function _smtp_required_field($str)
    {
        if (ee()->input->post('mail_protocol') == 'smtp' && trim((string) $str) == '') {
            ee()->form_validation->set_message('_smtp_required_field', lang('empty_stmp_fields'));

            return false;
        }

        return true;
    }
}
// END CLASS

// EOF
