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

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules('cache_type', 'lang:caches_to_clear', 'required|enum[all,page,tag,db]');

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            ee()->functions->clear_caching(ee()->input->post('cache_type'));

            if (ee()->input->post('cache_type') == 'all') {
                ee('CP/JumpMenu')->clearAllCaches();
            }

            ee()->view->set_message('success', lang('caches_cleared'), '', true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/cache'));
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
