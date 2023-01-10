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
 * Member Profile Publishing Settings Controller
 */
class Publishing extends Profile
{
    private $base_url = 'members/profile/publishing';

    /**
     * Publishing Settings
     */
    public function index()
    {
        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'include_in_author_list',
                    'desc' => 'include_in_author_list_desc',
                    'fields' => array(
                        'in_authorlist' => array(
                            'type' => 'yes_no',
                            'value' => $this->member->in_authorlist
                        )
                    )
                )
            )
        );

        if (! empty($_POST)) {
            if ($this->saveSettings($vars['sections'])) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('member_updated'))
                    ->addToBody(lang('member_updated_desc'))
                    ->defer();
                ee()->functions->redirect($this->base_url);
            }
        }

        ee()->view->base_url = $this->base_url;
        ee()->view->cp_page_title = lang('publishing_settings');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->view->cp_breadcrumbs = array_merge($this->breadcrumbs, [
            '' => lang('publishing_settings')
        ]);

        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
