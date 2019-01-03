<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Utilities;

/**
 * Cache Manager Controller
 */
class Cache extends Utilities {

	/**
	 * Cache Manager
	 *
	 * @access	public
	 * @return	void
	 */
	public function index()
	{
		if ( ! ee()->cp->allowed_group('can_access_data'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$vars['hide_top_buttons'] = TRUE;
		$vars['sections'] = array(
			array(
				array(
					'title' => 'caches_to_clear',
					'desc' => 'caches_to_clear_desc',
					'fields' => array(
						'cache_type' => array(
							'type' => 'radio',
							'choices' => array(
								'all'  => lang('all_caches'),
								'page' => lang('templates'),
								'tag'  => lang('tags'),
								'db'   => lang('database')
							),
							'value' => set_value('cache_type', 'all'),
							'required' => TRUE
						)
					)
				)
			)
		);


		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('cache_type', 'lang:caches_to_clear', 'required|enum[all,page,tag,db]');

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			ee()->functions->clear_caching(ee()->input->post('cache_type'));

			ee()->view->set_message('success', lang('caches_cleared'), '', TRUE);
			ee()->functions->redirect(ee('CP/URL')->make('utilities/cache'));
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('cache_manager');
		ee()->view->base_url = ee('CP/URL')->make('utilities/cache');
		ee()->view->save_btn_text = 'btn_clear_caches';
		ee()->view->save_btn_text_working = 'btn_clear_caches_working';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

// EOF
