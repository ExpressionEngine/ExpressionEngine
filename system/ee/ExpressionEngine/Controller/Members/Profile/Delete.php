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
 * Member Delete Controller
 */
class Delete extends Profile
{
    private $base_url = 'members/profile/delete';

    /**
     * Member deletion page
     */
    public function index()
    {
        if (! empty($_POST['member'])) {
            $this->deleteMember();
        }

        $this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'Members:',
                    'desc' => $this->member->username,
                    'fields' => array(
                        'member' => array(
                            'type' => 'hidden',
                            'value' => $this->member->member_id
                        )
                    )
                )
            )
        );

        ee('CP/Alert')->makeInline('shared-form')
            ->asWarning()
            ->cannotClose()
            ->withTitle(lang('delete_member_warning'))
            ->addToBody(lang('delete_member_caution'), 'txt-enhance')
            ->now();

        ee()->view->base_url = $this->base_url;
        ee()->view->cp_page_title = lang('member_delete');
        ee()->view->save_btn_text = 'btn_save_settings';
        ee()->view->save_btn_text_working = 'btn_saving';
        ee()->cp->render('settings/form', $vars);
    }

    private function deleteMember()
    {
        $this->member->delete();
        ee()->functions->redirect(ee('CP/URL')->make('members'));
    }
}
// END CLASS

// EOF
