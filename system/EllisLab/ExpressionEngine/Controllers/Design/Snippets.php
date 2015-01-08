<?php

namespace EllisLab\ExpressionEngine\Controllers\Design;

use EllisLab\ExpressionEngine\Controllers\Design\Design;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Design\Snippets Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Snippets extends Design {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_design', 'can_admin_templates'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->stdHeader();
	}

	public function index()
	{
		$msm = (ee()->config->item('multiple_sites_enabled') == 'y');

		$vars = array();
		$table = Table::create();
		$columns = array(
			'partial',
			'all_sites',
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		);

		if ( ! $msm)
		{
			unset($columns[1]);
		}

		$table->setColumns($columns);

		$data = array();
		$snippets = ee('Model')->get('Snippet')->all();

		$base_url = new URL('design/snippets', ee()->session->session_id());

		foreach($snippets as $snippet)
		{
			if ($snippet->site_id == 0)
			{
				$all_sites = '<b class="yes">' . lang('yes') . '</b>';
			}
			else
			{
				$all_sites = '<b class="no">' . lang('no') . '</b>';
			}
			$datum = array(
				$snippet->snippet_name,
				$all_sites,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => cp_url('design/snippets/edit/' . $snippet->snippet_id),
						'title' => lang('edit')
					),
					'find' => array(
						'href' => cp_url('design/snippets/find/' . $snippet->snippet_id),
						'title' => lang('find')
					),
				)),
				array(
					'name' => 'selection[]',
					'value' => $snippet->snippet_id,
					'data'	=> array(
						'confirm' => lang('template_partial') . ': <b>' . htmlentities($snippet->snippet_name, ENT_QUOTES) . '</b>'
					)
				)

			);

			if ( ! $msm)
			{
				unset($datum[1]);
			}
			$data[] = $datum;
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$pagination = new Pagination(
				$vars['table']['limit'],
				$vars['table']['total_rows'],
				$vars['table']['page']
			);
			$vars['pagination'] = $pagination->cp_links($base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('template_partial') . ': <b>### ' . lang('template_partials') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/v3/confirm_remove'),
		));

		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('template_partials_header');
		ee()->cp->render('design/snippets/index', $vars);
	}

}
// EOF