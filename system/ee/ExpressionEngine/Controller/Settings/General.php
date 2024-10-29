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
 * General Settings Controller
 */
class General extends Settings
{
    /**
     * General Settings
     */
    public function index()
    {
        ee()->load->model('admin_model');
        ee()->lang->loadfile('calendar');

        $site = ee('Model')->get('Site')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        $localization_fields = ee()->config->prep_view_vars('localization_cfg');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'site_name',
                    'fields' => array(
                        'site_name' => array(
                            'type' => 'text',
                            'value' => $site->site_label,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'site_short_name',
                    'desc' => 'alphadash_desc',
                    'fields' => array(
                        'site_short_name' => array(
                            'type' => 'text',
                            'value' => $site->site_name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'site_license_key',
                    'desc' => 'site_license_key_desc',
                    'fields' => array(
                        'site_license_key' => array(
                            'type' => 'text',
                            'value' => ee()->config->item('site_license_key'),
                            'required' => false
                        )
                    )
                ),
                'site_online' => array(
                    'title' => 'site_online',
                    'desc' => 'site_online_desc',
                    'fields' => array(
                        'is_system_on' => array(
                            'type' => 'yes_no',
                        )
                    )
                ),
                array(
                    'title' => 'version_autocheck',
                    'desc' => 'version_autocheck_desc',
                    'button' => array(
                        'text' => 'check_now',
                        'href' => ee('CP/URL', 'settings/general/version-check'),
                        'for' => 'version-check'
                    ),
                    'fields' => array(
                        'new_version_check' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'y' => 'auto',
                                'n' => 'manual'
                            )
                        )
                    )
                ),
                array(
                    'title' => 'enable_msm',
                    'desc' => 'enable_msm_desc',
                    'fields' => array(
                        'multiple_sites_enabled' => array(
                            'type' => 'yes_no',
                        )
                    )
                ),
                array(
                    'title' => 'show_ee_news',
                    'desc' => 'show_ee_news_desc',
                    'fields' => array(
                        'show_ee_news' => array(
                            'type' => 'yes_no',
                        )
                    )
                ),
            ),
            'defaults' => array(
                array(
                    'title' => 'language',
                    'desc' => 'used_in_cp_only',
                    'fields' => array(
                        'deft_lang' => array(
                            'type' => 'radio',
                            'choices' => ee()->lang->language_pack_names(),
                            'value' => ee()->config->item('deft_lang') ?: 'english'
                        )
                    )
                )
            ),
            'date_time_settings' => array(
                array(
                    'title' => 'timezone',
                    'fields' => array(
                        'default_site_timezone' => array(
                            'type' => 'html',
                            'content' => ee()->localize->timezone_menu(set_value('default_site_timezone') ?: ee()->config->item('default_site_timezone'))
                        )
                    )
                ),
                array(
                    'title' => 'date_time_fmt',
                    'desc' => 'used_in_cp_only',
                    'fields' => array(
                        'date_format' => array(
                            'type' => 'radio',
                            'choices' => $localization_fields['fields']['date_format']['value']
                        ),
                        'time_format' => array(
                            'type' => 'radio',
                            'choices' => array(
                                '24' => lang('24_hour'),
                                '12' => lang('12_hour')
                            )
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
                            )
                        )
                    )
                ),
                array(
                    'title' => 'include_seconds',
                    'desc' => 'include_seconds_desc',
                    'fields' => array(
                        'include_seconds' => array('type' => 'yes_no')
                    )
                ),
            ),
        );

        if (bool_config_item('multiple_sites_enabled')) {
            $vars['sections'][0]['site_online']['title'] = 'system_online';
            $vars['sections'][0]['site_online']['desc'] = sprintf(lang('system_online_desc'), ee('CP/URL', 'msm')->compile());
        }

        $base_url = ee('CP/URL', 'settings/general');

        ee()->form_validation->set_rules('site_name', 'lang:site_name', 'required|strip_tags|valid_xss_check');
        ee()->form_validation->set_rules('site_short_name', 'lang:site_short_name', 'required|alpha_dash|strip_tags|callback__validShortName|valid_xss_check');

        ee()->form_validation->validateNonTextInputs($vars['sections']);

        // Handle AJAX validation
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

        ee()->javascript->output(
            "
			var versionCheckRadio = $('input[name=new_version_check]');

			EE.cp.toggleVersionCheckBtn = function(input) {

				var button = $(input).parents('fieldset').find('a.action');

				button.toggle($(input).filter(':checked').val() == 'n');
			};

			EE.cp.toggleVersionCheckBtn(versionCheckRadio);

			versionCheckRadio.click(function(event) {
				EE.cp.toggleVersionCheckBtn($(this));
			});"
        );

        ee()->view->base_url = $base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('general_settings');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';
        ee()->view->cp_breadcrumbs = array(
            '' => lang('general_settings')
        );
        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Ensure the short name is valid
     * @param  string $short_name Short name for the site
     * @return boolean            TRUE if valid, FALSE if not
     */
    public function _validShortName($short_name)
    {
        $count = ee('Model')->get('Site')
            ->filter('site_id', '!=', ee()->config->item('site_id'))
            ->filter('site_name', $short_name)
            ->count();

        if ($count > 0) {
            ee()->form_validation->set_message(
                '_validShortName',
                lang('site_short_name_taken')
            );

            return false;
        }

        return true;
    }

    /**
     * Save the settings from General, but make sure to save site name and label
     * manually
     *
     * @param	array	$sections	Array of sections passed to form view
     * @return	bool	Success or failure of saving the settings
     */
    protected function saveSettings($sections)
    {
        // Remove site_name/label
        $site = ee('Model')->get('Site')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        // Site_name is the version of the name that's used in parameters and
        // must be all one word, no spaces. site_label is the version that's
        // more outward facing.
        $site->site_name = ee()->input->post('site_short_name');
        $site->site_label = ee()->input->post('site_name');
        $site->save();

        unset($sections[0][0]);
        unset($sections[0][1]);

        return parent::saveSettings($sections);
    }

    /**
     * General Settings
     */
    public function versionCheck()
    {
        ee()->load->library('el_pings');

        // Error getting version
        if (! ee()->el_pings->get_version_info(true)) {
            ee('CP/Alert')->makeBanner('error-getting-version')
                ->asIssue()
                ->withTitle(lang('cp_message_issue'))
                ->addToBody(sprintf(lang('error_getting_version'), APP_VER))
                ->defer();
        } else {
            $version_info = ee()->el_pings->getUpgradeInfo();

            // New version available
            if (!empty($version_info) && version_compare(ee()->config->item('app_version'), $version_info['version'], '<')) {
                $version_major = explode('.', APP_VER, 2)[0];
                $update_version_major = explode('.', $version_info['version'], 2)[0];

                $isVitalUpdate = $version_info['security'];
                $isMajorUpdate = version_compare($version_major, $update_version_major, '<');

                if (AJAX_REQUEST) {
                    return [
                        'isVitalUpdate' => $isVitalUpdate,
                        'isMajorUpdate' =>$isMajorUpdate,
                        'newVersionMarkup' => ee('View')->make('ee:_shared/_new_version')->render($version_info)
                    ];
                }

                $upgrade_url = ee('CP/URL', 'updater')->compile();
                $instruct_url = ee()->cp->masked_url('https://docs.expressionengine.com/latest/installation/update.html');

                $banner = ee('CP/Alert')->makeBanner('version-update-available')
                    ->asAttention()
                    ->canClose()
                    ->withTitle(sprintf(lang('version_update_available'), $version_info['version']));
                if ($isMajorUpdate) {
                    $banner->addToBody(lang('version_update_is_major') . '<br><br>');
                } elseif ($isVitalUpdate) {
                    $banner->addToBody(lang('version_update_is_vital') . '<br><br>');
                }
                if ($version_major < 7) {
                    if (ee('Model')->get('Member')->count() > 1 && (! IS_PRO || ee('Addon')->get('pro')->checkCachedLicenseResponse() !== 'valid')) {
                        $banner->addToBody(lang('one_click_major_update_pro_license_required') . '<br><br>');
                    }
                    $banner->addToBody(lang('one_click_major_update_pro_license_info') . '<br><br>');
                }
                $banner->addToBody(sprintf(lang('version_update_inst'), $upgrade_url, $instruct_url));
                $banner->defer();
            }
            // Running latest version already
            else {
                if (AJAX_REQUEST) {
                    return ['up-to-date'];
                }

                ee('CP/Alert')->makeBanner('running-current')
                    ->asSuccess()
                    ->withTitle(lang('running_current'))
                    ->addToBody(sprintf(lang('running_current_desc'), APP_VER))
                    ->defer();
            }
        }

        ee()->functions->redirect(ee('CP/URL', 'settings/general'));
    }
}
// END CLASS

// EOF
