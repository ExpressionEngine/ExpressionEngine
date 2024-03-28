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

/**
 * Cache Manager Controller
 */
class Cache extends Utilities
{
    /**
     * Cache Manager
     *
     * @access	public
     * @return	void
     */
    public function index()
    {
        if (! ee('Permission')->can('access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $vars['hide_top_buttons'] = true;
        $vars['sections'] = array(
            array(
                array(
                    'title' => 'caches_to_clear',
                    'desc' => 'caches_to_clear_desc',
                    'fields' => array(
                        'cache_type' => array(
                            'type' => 'radio',
                            'choices' => array(
                                'all' => lang('all_caches'),
                                'page' => lang('templates'),
                                'tag' => lang('tags'),
                                'db' => lang('database')
                            ),
                            'value' => set_value('cache_type', 'all'),
                            'required' => true
                        )
                    )
                )
            )
        );

        $validator = ee('Validation')->make();
        $validator->setRules(array(
            'cache_type' => 'required|enum[all,page,tag,db]',
        ));
        $result = $validator->validate($_POST);
        if ($response = $this->ajaxValidation($result)) {
            return $response;
        }

        if ($result->isValid()) {
            ee()->functions->clear_caching(ee()->input->post('cache_type'));

            if (ee()->input->post('cache_type') == 'all') {
                ee('CP/JumpMenu')->clearAllCaches();
            }

            if (AJAX_REQUEST) {
                // Jump menu request
                ee()->output->send_ajax_response(array(
                    'success' => true,
                    'message' => lang('caches_cleared')
                ));
            }

            ee()->view->set_message('success', lang('caches_cleared'), '', true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/cache'));
        } else {
            if (AJAX_REQUEST) {
                // jump menu request
                $validationErrors = [];
                foreach ($result->getAllErrors() as $field => $errors) {
                    foreach ($errors as $error) {
                        $validationErrors[] = '<b>' . lang($field) . ':</b> ' . $error;
                    }
                }
                ee()->output->send_ajax_response(array(
                    'status' => 'error',
                    'error' => true,
                    'message' => implode('<br>', $validationErrors)
                ));
            }
        }

        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('cache_manager');
        ee()->view->base_url = ee('CP/URL')->make('utilities/cache');
        ee()->view->save_btn_text = 'btn_clear_caches';
        ee()->view->save_btn_text_working = 'btn_clear_caches_working';

        ee()->view->cp_breadcrumbs = array(
            '' => lang('cache_manager')
        );

        ee()->cp->render('settings/form', $vars);
    }
}
// END CLASS

// EOF
