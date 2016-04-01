<?php

namespace EllisLab\ExpressionEngine\Controller\Channels;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP;
use EllisLab\ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Channel Manager Search Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Search extends AbstractChannelsController {

	public function index()
	{
		if ( ! isset($_POST['search']) && ! isset($_GET['search']))
		{
			ee()->functions->redirect(ee('CP/URL')->make('channels'));
		}

		$search_terms = ee()->input->get_post('search');

		$vars = array('results' => array());

		$search_sections = array(
			'channels' => array(
				'query' => ee('Model')->get('Channel')
					->filter('site_id', ee()->config->item('site_id'))
					->filterGroup()
					->filter('channel_name', 'LIKE', '%' . $search_terms . '%')
					->orFilter('channel_title', 'LIKE', '%' . $search_terms . '%')
					->endFilterGroup(),
				'table_create_method' => 'buildTableFromChannelQuery',
			),
			'custom_fields' => array(
				'query' => ee('Model')->get('ChannelField')
					->filter('site_id', ee()->config->item('site_id'))
					->filterGroup()
					->filter('field_label', 'LIKE', '%' . $search_terms . '%')
					->orFilter('field_name', 'LIKE', '%' . $search_terms . '%')
					->endFilterGroup(),
				'table_create_method' => 'buildTableFromChannelFieldsQuery',
			),
			'field_groups' => array(
				'query' => ee('Model')->get('ChannelFieldGroup')
					->filter('site_id', ee()->config->item('site_id'))
					->filter('group_name', 'LIKE', '%' . $search_terms . '%'),
				'table_create_method' => 'buildTableFromChannelGroupsQuery',
			),
			'category_groups' => array(
				'query' => ee('Model')->get('CategoryGroup')
					->filter('site_id', ee()->config->item('site_id'))
					->filter('group_name', 'LIKE', '%' . $search_terms . '%'),
				'table_create_method' => 'buildTableFromCategoryGroupsQuery',
			),
			'categories' => array(
				'query' => ee('Model')->get('Category')
					->filter('site_id', ee()->config->item('site_id'))
					->filterGroup()
					->filter('cat_name', 'LIKE', '%' . $search_terms . '%')
					->orFilter('cat_url_title', 'LIKE', '%' . $search_terms . '%')
					->orFilter('cat_description', 'LIKE', '%' . $search_terms . '%')
					->endFilterGroup(),
				'table_create_method' => 'buildTableFromCategoriesQuery',
			),
			'status_groups' => array(
				'query' => ee('Model')->get('StatusGroup')
					->filter('site_id', ee()->config->item('site_id'))
					->filter('group_name', 'LIKE', '%' . $search_terms . '%'),
				'table_create_method' => 'buildTableFromStatusGroupsQuery',
			)
		);

		$safe_search_terms = htmlentities($search_terms);

		foreach ($search_sections as $name => $section)
		{
			$query = $section['query'];
			$table_create = $section['table_create_method'];

			$total_rows = $query->count();

			if ($total_rows)
			{
				$page = ee()->input->get($name . '_page') ?: 1;

				$table = $this->$table_create(
					$query,
					array(
						'sort_col_qs_var'	=> $name . '_sort_col',
						'sort_dir_qs_var'	=> $name . '_sort_dir',
						'page'				=> $page
					),
					FALSE
				);

				$base_url = ee('CP/URL')->make('channels/search',	ee()->cp->get_url_state())
					->setQueryStringVariable('search', $search_terms);

				$vars['results'][] = array(
					'heading'		=> lang($name).'<br><i>'.sprintf(lang('section_search_results'), $safe_search_terms).'</i>',
					'table'			=> $table->viewData($base_url),
					'total_rows'	=> $total_rows,
					'name'			=> $name
				);
			}
		}

		if (empty($vars['results']))
		{
			$base_url = ee('CP/URL')->make('channels/search',	ee()->cp->get_url_state())
				->setQueryStringVariable('search', $search_terms);
			$table = ee('CP/Table');

			$vars['results'][] = array(
				'heading'		=> sprintf(lang('search_results_heading'), 0, $safe_search_terms),
				'table'			=> $table->viewData($base_url),
				'total_rows'	=> 0,
				'name'			=> lang('search_results')
			);
		}

		$vars['cp_page_title'] = sprintf(lang('search_for'), $safe_search_terms);

		return ee()->cp->render('channels/search', $vars);
	}

}

// EOF
