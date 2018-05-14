<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Member Profile Consent Controller
 */
class Consent extends Settings {

	private $base_url = 'members/profile/consent';

	public function __construct()
	{
		parent::__construct();
		$this->base_url  = ee('CP/URL')->make($this->base_url, $this->query_string);
	}

	public function index()
	{
		if (ee()->input->post('bulk_action') == 'opt_in')
		{
			$this->optIn(ee()->input->post('selection'));
			ee()->functions->redirect($this->base_url);
		}
		elseif (ee()->input->post('bulk_action') == 'opt_out')
		{
			$this->optOut(ee()->input->post('selection'));
			ee()->functions->redirect($this->base_url);
		}

		$table = ee('CP/Table');
		$vars = [
			'base_url' => $this->base_url,
		];

		$requests = ee('Model')->get('ConsentRequest')
			->with('CurrentVersion');

		if ($search = ee()->input->get_post('filter_by_keyword'))
		{
			$requests = $requests->search('title', $search);
		}

		$total_requests = $requests->count();

		$filters = ee('CP/Filter')
			->add('Date')
			->add('Keyword')
			->add('Perpage', $total_requests, 'all_consents', TRUE);
		$filter_values = $filters->values();

		$page = ee('Request')->get('page') ?: 1;
		$per_page = $filter_values['perpage'];

		$requests = $requests->offset(($page - 1) * $per_page)
			->limit($per_page)
			->order('title')
			->all();

		// Only show filters if there is data to filter or we are currently filtered
		if ($search OR $requests->count() > 0)
		{
			$vars['filters'] = $filters->render($vars['base_url']);
		}

		$highlight_id = ee()->session->flashdata('highlight_id');

		$data = [];

		$consents = ($this->member->Consents->count()) ? $this->member->Consents->indexBy('consent_request_id') : [];

		if ( ! empty($filter_values['filter_by_date']))
		{
			$consents = array_filter($consents, function($consent) use($filter_values){
				if (is_array($filter_values['filter_by_date']))
				{
					return ($consent->update_date->format('U') >= $filter_values['filter_by_date'][0] && $consent->update_date->format('U') < $filter_values['filter_by_date'][1]);
				}
				else
				{
					return ($consent->update_date->format('U') >= (ee()->localize->now - $filter_values['filter_by_date']));
				}
			});
		}

		$vars['requests'] = $requests;

		foreach ($requests as $request)
		{
			$toolbar = [
				'toolbar_items' => [
					'view' => [
						'href' => '',
						'rel' => 'modal-consent-request-' . $request->getId(),
						'title' => strtolower(lang('view')),
						'class' => 'js-modal-link'
					]
				]
			];

			$status = [
				'class' => 'draft',
				'content' => lang('needs_review'),
			];

			$date = NULL;

			if (array_key_exists($request->getId(), $consents))
			{
				$consent = $consents[$request->getId()];
				$date = ee()->localize->human_time($consent->update_date->format('U'));

				if ($consent->isGranted())
				{
					$status = [
						'class' => 'open',
						'content' => lang('yes'),
					];
				}
				else
				{
					$status = [
						'class' => 'closed',
						'content' => lang('no'),
					];
				}
			}

			if ( ! empty($filter_values['filter_by_date']) && is_null($date))
			{
				continue;
			}

			$data[] = [
				'name' => $request->title,
				'date' => ($date) ?: '-',
				'status' => $status,
				$toolbar,
				'selection' => [
					'name' => 'selection[]',
					'value' => $request->getId()
				]
			];
		}

		$table->setColumns(
			[
				'name' => [
					'encode' => FALSE
				],
				'date',
				'status' => [
					'type' => Table::COL_STATUS
				],
				'manage' => [
					'type'=> Table::COL_TOOLBAR
				],
				[
					'type'=> Table::COL_CHECKBOX
				]
			]
		);

		$table->setNoResultsText('no_consents_found');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $this->base_url->compile();

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('consents');
		ee()->cp->render('account/consents', $vars);
	}

	protected function optIn(array $request_ids)
	{
		foreach ($request_ids as $request_id)
		{
			ee('Consent', $this->member)->grant($request_id);
		}
	}

	protected function optOut(array $request_ids)
	{
		foreach ($request_ids as $request_id)
		{
			ee('Consent', $this->member)->withdraw($request_id);
		}
	}

}
// END CLASS

// EOF
