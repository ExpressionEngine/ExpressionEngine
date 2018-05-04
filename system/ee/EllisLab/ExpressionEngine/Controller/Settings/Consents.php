<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Controller\Settings;

use EllisLab\ExpressionEngine\Service\Model\Query\Builder;
use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Consents Controller
 */
class Consents extends Settings {

	public function index()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL')->make('settings/consents'));
		}

		$vars['base_url'] = ee('CP/URL', 'settings/consents');

		$requests = ee('Model')->get('ConsentRequest');

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

		foreach ($requests as $request)
		{
			$edit_url = ee('CP/URL')->make('settings/consents/' . $request->getId());

			$data[] = [
				'id' => $request->getId(),
				'label' => $request->title,
				'href' => $edit_url,
				'selected' => ($highlight_id && $request->getId() == $highlight_id),
				'toolbar_items' => [
					'edit' => [
						'href' => $edit_url,
						'title' => lang('edit')
							]
				],
				'selection' => [
					'name' => 'selection[]',
					'value' => $request->getId(),
					'data' => [
						'confirm' => lang('consent') . ': <b>' . ee('Format')->make('Text', $request->title)->convertToEntities() . '</b>'
					]
				]
			];
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('layout') . ': <b>### ' . lang('consents') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		$vars['pagination'] = ee('CP/Pagination', $total_requests)
			->perPage($per_page)
			->currentPage($page)
			->render(ee('CP/URL')->make('settings/consents', $total_requests));

		$vars['cp_page_title'] = lang('consents');
		$vars['requests'] = $data;
		$vars['create_url'] = ee('CP/URL', 'settings/consents/create');
		$vars['no_results'] = ['text' =>
			sprintf(lang('no_found'), lang('consents'))
			.' <a href="'.$vars['create_url'].'">'.lang('add_new').'</a>'];

		ee()->cp->render('settings/consents/index', $vars);
	}

	public function create()
	{
		return $this->form();
	}

	public function edit($request_id)
	{
		return $this->form($request_id);
	}

	public function remove()
	{
		$request_ids = ee()->input->post('selection');
		$requests = ee('Model')->get('Channel', $request_ids)
			->filter('source', 'u')
			->all();

		if ($requests->count() > 0 && ee()->input->post('bulk_action') == 'remove')
		{
			$requests->delete();

			ee('CP/Alert')->makeInline('requests')
				->asSuccess()
				->withTitle(lang('requests_removed'))
				->addToBody(sprintf(lang('requests_removed_desc'), count($request_ids)))
				->defer();
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(ee('CP/URL')->make('settings/consents/create', ee()->cp->get_url_state()));
	}

	private function form($request_id = NULL)
	{
		if (is_null($request_id))
		{
			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('create_new_consent_request');
			ee()->view->base_url = ee('CP/URL')->make('settings/consents/create');

			$request = ee('Model')->make('ConsentRequest');
			$request->source = 'u';
			$version = $this->makeNewVersion();
		}
		else
		{
			$request = ee('Model')->get('ConsentRequest', $request_id)
				->with('CurrentVersion')
				->first();

			if ( ! $request)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			// If the current version of the request has any consents, then we are making
			// a new version.
			if ($request->CurrentVersion->Consents->count())
			{
				$version = $this->makeNewVersion();
			}
			else
			{
				$request->CurrentVersion->last_author_id = ee()->session->userdata['member_id'];
				$request->CurrentVersion->edit_date = ee()->localize->now;

				$version = $request->CurrentVersion;
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_consent_request');
			ee()->view->breadcrumb_title = lang('edit').' '.$request->title;
			ee()->view->base_url = ee('CP/URL')->make('settings/consents/edit/'.$request_id);
		}

		$vars['errors'] = NULL;

		if ( ! empty($_POST))
		{
			$request->set($_POST);
			$version->set($_POST);
			$result = $request->validate();

			if ($result->isValid())
			{
				$request->save();

				$version->consent_request_id = $request->getId();
				$version->save();

				$request->consent_request_version_id = $version->getId();
				$request->save();

				if (is_null($request_id))
				{
					ee()->session->set_flashdata('highlight_id', $request->getId());
				}

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('channel_'.$alert_key))
					->addToBody(sprintf(lang('channel_'.$alert_key.'_desc'), $request->title))
					->defer();

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('settings/consents/create'));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('channels'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('settings/consents/edit/'.$request->getId()));
				}
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('consent_request_not_'.$alert_key))
					->addToBody(lang('consent_request_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		ee()->load->model('addons_model');
		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$vars['sections'] = [
			[
				[
					'title' => 'title',
					'fields' => [
						'title' => [
							'type' => 'text',
							'value' => $request->title,
							'required' => TRUE
						]
					]
				],
				[
					'title' => 'url_title',
					'desc' => 'alphadash_desc',
					'fields' => [
						'url_title' => [
							'type' => 'text',
							'value' => $request->url_title,
							'required' => TRUE
						]
					]
				],
				[
					'title' => 'request',
					'fields' => [
						'request' => [
							'type' => 'textarea',
							'value' => $version->request,
							'required' => TRUE
						]
					]
				],
				[
					'title' => 'request_format',
					'fields' => [
						'request_format' => [
							'type' => 'radio',
							'choices' => $format_options,
							'value' => $version->request_format,
						]
					]
				]
			]
		];

		$vars['buttons'] = [
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save',
				'text' => 'save',
				'working' => 'btn_saving'
			],
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_new',
				'text' => 'save_and_new',
				'working' => 'btn_saving'
			],
			[
				'name' => 'submit',
				'type' => 'submit',
				'value' => 'save_and_close',
				'text' => 'save_and_close',
				'working' => 'btn_saving'
			]
		];

		ee()->cp->add_js_script('plugin', 'ee_url_title');

		ee()->cp->render('settings/form', $vars);
	}

	private function makeNewVersion()
	{
		$version = ee('Model')->make('ConsentRequestVersion');
		$version->author_id = ee()->session->userdata['member_id'];
		$version->last_author_id = ee()->session->userdata['member_id'];
		$version->create_date = ee()->localize->now;
		$version->edit_date = ee()->localize->now;

		return $version;
	}
}
