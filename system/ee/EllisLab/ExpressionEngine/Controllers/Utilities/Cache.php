<?php

namespace EllisLab\ExpressionEngine\Controllers\Utilities;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Cache Manager Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
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
		if ( ! ee()->cp->allowed_group('can_access_tools', 'can_access_data'))
		{
			show_error(lang('unauthorized_access'));
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'caches_to_clear',
					'desc' => 'caches_to_clear_desc',
					'fields' => array(
						'cache_type' => array(
							'type' => 'checkbox',
							'choices' => array(
								'page' => lang('templates'),
								'tag'  => lang('tags'),
								'db'   => lang('database'),
								'all'  => lang('all')
							),
							'value' => set_value('cache_type', 'all'),
							'required' => TRUE
						)
					)
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules('cache_type[]', 'lang:caches_to_clear', 'required');

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			// Clear each cache type checked
			foreach (ee()->input->post('cache_type') as $type)
			{
				ee()->functions->clear_caching($type);
			}

			ee()->view->set_message('success', lang('caches_cleared'), '', TRUE);
			ee()->functions->redirect(cp_url('utilities/cache'));
		}

		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('cache_manager');
		ee()->view->base_url = cp_url('utilities/cache');
		ee()->view->save_btn_text = 'btn_clear_caches';
		ee()->view->save_btn_text_working = 'btn_clear_caches_working';
		ee()->cp->render('settings/form', $vars);
	}
}
// END CLASS

/* End of file Cache.php */
/* Location: ./system/EllisLab/ExpressionEngine/Controllers/Utilities/Cache.php */
