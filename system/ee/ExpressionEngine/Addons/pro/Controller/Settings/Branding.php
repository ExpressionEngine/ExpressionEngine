<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Controller\Settings;

use ExpressionEngine\Controller\Settings;
use ExpressionEngine\Library\CP\Table;

/**
 * Branding Controller
 */
class Branding extends Settings\Pro
{
    public function __construct()
    {
        ee()->load->library('file_field');

        $this->base_url = ee('CP/URL')->make('settings/pro/branding');
    }

    /**
     * Branding Settings Page
     */
    public function branding($segments)
    {
        $fields = [
            'login_logo',
            'favicon'
        ];

        if (ee('Request')->isPost()) {
            $data = array();
            foreach ($fields as $field) {
                $data[$field] = ee()->file_field->parse_string(ee()->input->post($field));
            }

            $config_update = ee()->config->update_site_prefs($data);

            if (!empty($config_update)) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('cp_message_issue'))
                    ->addToBody($config_update)
                    ->defer();
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody(lang('pro_settings_updated'))
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('settings/pro/branding'));
        }

        $settings = [];
        foreach ($fields as $field) {
            $settings[$field] = $this->unparseFiledir(ee()->config->item($field));
        }

        $vars = [
            'sections' => [
                [
                    [
                        'title' => 'login_logo',
                        'desc' => 'login_logo_desc',
                        'fields' => [
                            'login_logo' => [
                                'type' => 'html',
                                'value' => $settings['login_logo'],
                                'required' => false,
                                'maxlength' => 255,
                                'content' => ee()->file_field->dragAndDropField('login_logo', $settings['login_logo'], 'all', 'image')
                            ]
                        ]
                    ],
                    [
                        'title' => 'favicon',
                        'desc' => 'favicon_desc',
                        'fields' => [
                            'favicon' => [
                                'type' => 'html',
                                'value' => $settings['favicon'],
                                'required' => false,
                                'maxlength' => 255,
                                'content' => ee()->file_field->dragAndDropField('favicon', $settings['favicon'], 'all', 'image')
                            ]
                        ]
                    ]
                ]
            ]
        ];

        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/publish/entry-list',
            ),
        ));

        $vars += array(
            'base_url' => ee('CP/URL')->make('settings/pro/branding'),
            'cp_page_title' => lang('branding_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        ee()->view->cp_breadcrumbs = array(
            '' => lang('branding_settings')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * Convert real path back to {filedir_X}
     */
    private function unparseFiledir($path)
    {
        $dirs = ee('Model')->get('UploadDestination')
            ->all()
            ->getDictionary('id', 'url');
        foreach ($dirs as $id => $url) {
            $path = str_replace($url, '{filedir_' . $id . '}', $path);
        }

        return $path;
    }
}

// EOF
