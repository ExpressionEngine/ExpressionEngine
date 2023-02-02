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
 * Cookies Controller
 */
class Cookies extends Settings\Pro
{
    public function __construct()
    {
        ee()->view->header = array(
            'title' => lang('cookie_settings'),
        );
        $this->base_url = ee('CP/URL')->make('settings/pro/cookies');
    }

    /**
     * List all cookies
     */
    public function cookies($segments)
    {
        $vars = array();
        $cookie_prefix = (! ee()->config->item('cookie_prefix')) ? 'exp_' : ee()->config->item('cookie_prefix') . '_';

        $allCookies = ee('Model')->get('CookieSetting')->all();
        $providers = ee('App')->getProviders();

        foreach (['Necessary', 'Functionality', 'Performance', 'Targeting'] as $type) {
            $data = array();
            foreach ($allCookies as $cookie) {
                $fn = 'is' . $type;
                if (ee('CookieRegistry')->{$fn}($cookie->cookie_name)) {
                    $data[] = [
                        $cookie->cookie_title,
                        $cookie_prefix . $cookie->cookie_name,
                        isset($providers[$cookie->cookie_provider]) ? $providers[$cookie->cookie_provider]->getName() : lang($cookie->cookie_provider),
                        ($cookie->cookie_enforced_lifetime !== null) ? $cookie->cookie_enforced_lifetime : $cookie->cookie_lifetime,
                        ['toolbar_items' => ['settings' => [
                            'href' => ee('CP/URL')->make('settings/pro/cookies/manage/' . $cookie->cookie_id),
                            'title' => lang('settings'),
                        ]]]
                    ];
                }
            }
            $table = ee('CP/Table', array('autosort' => true, 'autosearch' => true));
            $table->setColumns(
                array(
                    'cookie_title',
                    'cookie_name',
                    'cookie_provider',
                    'cookie_lifetime',
                    'manage' => array(
                        'type' => Table::COL_TOOLBAR
                    )
                )
            );
            $table->setNoResultsText('no_cookies_registered');
            $table->setData($data);

            $vars['tables'][] = [
                'heading' => lang(strtolower($type) . '_cookies'),
                'table' => $table->viewData($this->base_url)
            ];
        }

        ee()->view->cp_breadcrumbs = array(
            '' => lang('cookie_settings')
        );

        ee()->view->cp_page_title = lang('cookie_settings');
        ee()->view->cp_heading = lang('cookie_settings');

        ee()->cp->render('pro:settings/cookies', $vars);
    }

    /**
     * Manage cookie settings
     *
     * @param int $id CookieSetting ID
     * @return string
     */
    public function manage($id)
    {
        $cookie = ee('Model')->get('CookieSetting', $id)->first();

        if (empty($cookie)) {
            show_error(lang('unauthorized_access'), 403);
        }

        $providers = ee('App')->getProviders();
        $provider = $cookie->cookie_provider == 'cp' ? 'ee' : $cookie->cookie_provider;
        if (!isset($providers[$provider])) {
            show_error(lang('unauthorized_access'), 403);
        }

        $cookieSettings = $providers[$provider]->get('cookie_settings');
        $lifetimeIsChangeable = true;
        if (!empty($cookieSettings) && isset($cookieSettings[$cookie->cookie_name])) {
            if (isset($cookieSettings[$cookie->cookie_name]['lifetime_changeable'])) {
                $lifetimeIsChangeable = (bool) $cookieSettings[$cookie->cookie_name]['lifetime_changeable'];
            }
        }

        $cookie_prefix = (! ee()->config->item('cookie_prefix')) ? 'exp_' : ee()->config->item('cookie_prefix') . '_';

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'cookie_title',
                    'desc' => 'cookie_title_desc',
                    'fields' => array(
                        'cookie_title' => array(
                            'type' => 'text',
                            'value' => $cookie->cookie_title
                        )
                    )
                ),
                array(
                    'title' => 'cookie_description',
                    'desc' => 'cookie_description_desc',
                    'fields' => array(
                        'cookie_description' => array(
                            'type' => 'textarea',
                            'value' => $cookie->cookie_description
                        )
                    )
                ),
                array(
                    'title' => 'cookie_lifetime',
                    'desc' => 'cookie_lifetime_desc',
                    'fields' => array(
                        'cookie_lifetime' => array(
                            'type' => 'text',
                            'value' => ($cookie->cookie_enforced_lifetime !== null) ? $cookie->cookie_enforced_lifetime : $cookie->cookie_lifetime,
                            'disabled' => !$lifetimeIsChangeable
                        )
                    )
                )
            )
        );

        if (! empty($_POST)) {
            $cookie->cookie_title = ee('Security/XSS')->clean(ee('Request')->post('cookie_title'));
            $cookie->cookie_description = ee('Security/XSS')->clean(ee('Request')->post('cookie_description'));
            if ($lifetimeIsChangeable && ee('Request')->post('cookie_lifetime') != $cookie->cookie_lifetime) {
                $cookie->cookie_enforced_lifetime = ee('Security/XSS')->clean(ee('Request')->post('cookie_lifetime'));
            }
            $result = $cookie->validate();
            if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result)) {
                return ee()->output->send_ajax_response($response);
            }
            if ($result->isValid()) {
                $cookie->save();
                ee()->functions->redirect($this->base_url);
            } else {
                $vars['errors'] = $result;
            }
        }

        ee()->view->base_url = ee('CP/URL')->make('settings/pro/cookies/manage/' . $cookie->cookie_id);
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = $cookie_prefix . $cookie->cookie_name;
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';
        ee()->view->cp_breadcrumbs = array(
            $this->base_url->compile() => lang('cookie_settings'),
            '' => $cookie->cookie_title
        );
        ee()->cp->render('settings/form', $vars);
    }
}

// EOF
