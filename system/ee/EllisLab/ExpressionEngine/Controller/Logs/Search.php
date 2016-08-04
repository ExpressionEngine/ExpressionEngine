<?php

namespace EllisLab\ExpressionEngine\Controller\Logs;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Service\CP\Filter\FilterFactory;
use EllisLab\ExpressionEngine\Service\CP\Filter\FilterRunner;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Search extends Logs {

	/**
	 * View Search Log
	 *
	 * Shows a log of recent search terms
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function index()
	{
		if ( ! $this->search_installed)
		{
			show_404();
		}

		if ( ! ee()->cp->allowed_group('can_access_logs'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee('CP/Alert')->makeDeprecationNotice()->now();

		if (ee()->input->post('delete'))
		{
			$this->delete('SearchLog', lang('search_log'));
			if (strtolower(ee()->input->post('delete')) == 'all')
			{
				return ee()->functions->redirect(ee('CP/URL')->make('logs/search'));
			}
		}

		$this->base_url->path = 'logs/search';
		ee()->view->cp_page_title = lang('view_search_log');

		$logs = ee('Model')->get('SearchLog')->with('Site');

		if ( ! empty(ee()->view->search_value))
		{
			$logs = $logs->filterGroup()
			               ->filter('screen_name', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('ip_address', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('search_type', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('search_terms', 'LIKE', '%' . ee()->view->search_value . '%')
			               ->orFilter('Site.site_label', 'LIKE', '%' . ee()->view->search_value . '%')
						 ->endFilterGroup();
		}

		if ($logs->count() > 10)
		{
			$filters = ee('CP/Filter')
				->add('Username')
				->add('Site')
				->add('Date')
				->add('Perpage', $logs->count(), 'all_search_logs');
			ee()->view->filters = $filters->render($this->base_url);
			$this->params = $filters->values();
			$this->base_url->addQueryStringVariables($this->params);
		}

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $this->params['perpage']; // Offset is 0 indexed

		if ( ! empty($this->params['filter_by_username']))
		{
			$logs = $logs->filter('member_id', 'IN', $this->params['filter_by_username']);
		}

		if ( ! empty($this->params['filter_by_site']))
		{
			$logs = $logs->filter('site_id', $this->params['filter_by_site']);
		}

		if ( ! empty($this->params['filter_by_date']))
		{
			if (is_array($this->params['filter_by_date']))
			{
				$logs = $logs->filter('search_date', '>=', $this->params['filter_by_date'][0]);
				$logs = $logs->filter('search_date', '<', $this->params['filter_by_date'][1]);
			}
			else
			{
				$logs = $logs->filter('search_date', '>=', ee()->localize->now - $this->params['filter_by_date']);
			}
		}

		$count = $logs->count();

		// Set the page heading
		if ( ! empty(ee()->view->search_value))
		{
			ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
		}

		ee()->view->header = array(
			'title' => lang('system_logs'),
			'form_url' => $this->base_url->compile(),
			'search_button_value' => lang('search_logs_button')
		);

		$logs = $logs->order('search_date', 'desc')
			->limit($this->params['perpage'])
			->offset($offset)
			->all();

		$pagination = ee('CP/Pagination', $count)
			->perPage($this->params['perpage'])
			->currentPage($page)
			->render($this->base_url);

		$vars = array(
			'logs' => $logs,
			'pagination' => $pagination,
			'form_url' => $this->base_url->compile(),
		);

		ee()->cp->render('logs/search', $vars);
	}
}
// END CLASS

// EOF
