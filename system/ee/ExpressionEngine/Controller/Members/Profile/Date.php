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

/**
 * Member Profile Date Settings Controller
 */
class Date extends Settings
{
    private $base_url = 'members/profile/date';

    /**
     * Date Settings
     */
    public function index()
    {
        ee()->lang->loadfile('calendar');
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
        $fields = ee()->config->prep_view_vars('localization_cfg');
        $fields = $fields['fields'];
        $timezone = ee()->localize->timezone_menu($this->member->timezone ?: ee()->config->item('default_site_timezone'), 'timezone');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'site_default',
                    'fields' => array(
                        'site_default' => array(
                            'type' => 'yes_no',
                            'value' => (empty($this->member->timezone) && empty($this->member->date_format)) ? 'y' : 'n',
                            'group_toggle' => array(
                                'n' => 'localize'
                            )
                        )
                    )
                ),
                array(
                    'title' => 'timezone',
                    'group' => 'localize',
                    'fields' => array(
                        'timezone' => array(
                            'type' => 'html',
                            'content' => $timezone
                        )
                    )
                ),
                array(
                    'title' => 'date_format',
                    'desc' => 'used_in_cp_only',
                    'group' => 'localize',
                    'fields' => array(
                        'date_format' => array(
                            'type' => 'radio',
                            'choices' => $fields['date_format']['value'],
                            'value' => $this->member->date_format
                        ),
                        'time_format' => array(
                            'type' => 'radio',
                            'choices' => array(12 => lang('12_hour'), 24 => lang('24_hour')),
                            'value' => $this->member->time_format
                        )
                    )
                ),
                array(
                    'title' => 'week_start',
                    'desc' => 'week_start_desc',
                    'fields' => array(
                        'week_start' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'friday' => lang('cal_friday'),
                                'saturday' => lang('cal_saturday'),
                                'sunday' => lang('cal_sunday'),
                                'monday' => lang('cal_monday')
                            ),
                            'value' => !empty($this->member->week_start) ? $this->member->week_start : (!empty(ee()->config->item('week_start')) ? ee()->config->item('week_start') : 'sunday')
                        )
                    )
                ),
                array(
                    'title' => 'include_seconds',
                    'desc' => 'include_seconds_desc',
                    'group' => 'localize',
                    'fields' => array(
                        'include_seconds' => array(
                            'type' => 'yes_no',
                            'value' => $this->member->include_seconds
                        )
                    )
                )
            )
        );

        ee()->form_validation->set_rules(array(
            array(
                'field' => 'site_default',
                'label' => 'lang:site_default',
                'rules' => 'required'
            )
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $success = false;
            if (ee()->input->post('site_default') == 'y') {
                /* @TODO Use models when models can set NULL
                $this->member->timezone = NULL;
                $this->member->date_format = NULL;
                $this->member->time_format = NULL;
                $this->member->include_seconds = NULL;
                $this->member->save();
                */
                ee()->db->set('timezone', null);
                ee()->db->set('date_format', null);
                ee()->db->set('time_format', null);
                ee()->db->set('week_start', null);
                ee()->db->set('include_seconds', null);
                ee()->db->where('member_id', $this->member->member_id);
                ee()->db->update('members');
                $success = true;
            } else {
                $success = $this->saveSettings($vars['sections']);
            }

            if ($success) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('member_updated'))
                    ->addToBody(lang('member_updated_desc'))
                    ->defer();
                ee()->functions->redirect($this->base_url);
            }
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('settings_save_error'))
                ->addToBody(lang('settings_save_error_desc'))
                ->now();
        }

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
        ));

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('date_settings');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            '' => lang('date_settings')
        ]);

        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
