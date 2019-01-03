<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Settings;

use EllisLab\ExpressionEngine\Model\Consent\ConsentRequest;
use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Consents Controller
 */
class Consents extends Settings {

	function __construct()
	{
		parent::__construct();

		ee('CP/Alert')->makeDeprecationNotice()->now();

		if ( ! ee('Permission')->has('can_manage_consents'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->lang->loadfile('consent');
	}

	public function index()
	{
		if (bool_config_item('require_cookie_consent') !== TRUE)
		{
			ee('CP/Alert')->makeInline('no-cookie-consent')
				->asWarning()
				->cannotClose()
				->withTitle(lang('cookie_consent_disabled'))
				->addToBody(sprintf(lang('cookie_consent_disabled_desc'), ee('CP/URL')->make('settings/security-privacy')->compile()))
				->now();
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL')->make('settings/consents'));
		}

		$vars = [
			'base_url'      => ee('CP/URL', 'settings/consents'),
			'cp_page_title' =>lang('consent_requests'),
			'create_url'    => ee('CP/URL', 'settings/consents/create'),
			'filters'       => [
				'app'  => NULL,
				'user' => NULL
			],
			'requests'      => [
				'app'  => [],
				'user' => []
			],
			'heading'       => [
				'app'  => lang('app_consent_requests'),
				'user' => lang('user_consent_requests')
			]
		];

		foreach (['app', 'user'] as $type)
		{
			$data = $this->buildTableDataFor($type);
			$vars['filters'][$type] = $data['filters']->render($vars['base_url']);
			$vars['requests'][$type] = $data['requests'];
		}

		$vars['no_results'] = ['text' =>
			sprintf(lang('no_found'), lang('consent_requests'))
			.' <a href="'.$vars['create_url'].'">'.lang('add_new').'</a>'];

		ee()->javascript->set_global('lang.remove_confirm', lang('consent_request') . ': <b>### ' . lang('consent_requests') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		ee()->cp->render('settings/consents/index', $vars);
	}

	private function buildTableDataFor($type)
	{
		switch ($type)
		{
			case 'user':
				$label = 'user';
				$user_created = 'y';
				break;
			case 'app':
			default:
				$label = 'app';
				$user_created = 'n';
		}

		$requests = ee('Model')->get('ConsentRequest')
			->filter('user_created', $user_created);

		if ($search = ee()->input->get_post('filter_by_' . $label . '_keyword'))
		{
			$requests = $requests->search('title', $search);
		}

		$total_requests = $requests->count();

		$filters = ee('CP/Filter')
			->add('Date')->withName('filter_by_' . $label . '_date')
			->add('Keyword')->withName('filter_by_' . $label . '_keyword');
		$filter_values = $filters->values();

		if ( ! empty($filter_values['filter_by_' . $label . '_date']))
		{
			$requests->with('CurrentVersion');

			if (is_array($filter_values['filter_by_' . $label . '_date']))
			{
				$requests->filter('CurrentVersion.create_date', '>=', $filter_values['filter_by_' . $label . '_date'][0]);
				$requests->filter('CurrentVersion.create_date', '<', $filter_values['filter_by_' . $label . '_date'][1]);
			}
			else
			{
				$requests->filter('CurrentVersion.create_date', '>=', ee()->localize->now - $filter_values['filter_by_' . $label . '_date']);
			}
		}

		$requests = $requests
			->order('title')
			->all();

		$highlight_id = ee()->session->flashdata('highlight_id');

		$data = [];

		foreach ($requests as $request)
		{
			$toolbar = [];
			$href = ee('CP/URL')->make('settings/consents/new_version/' . $request->getId());

			$toolbar['add'] = [
				'href'  => ee('CP/URL')->make('settings/consents/new_version/' . $request->getId()),
				'title' => lang('new_version'),
			];

			$toolbar['view'] = [
				'href'  => ee('CP/URL')->make('settings/consents/versions/' . $request->getId()),
				'title' => lang('consent_list_versions'),
			];

			$datum = [
				'id' => $request->getId(),
				'label' => $request->title,
				'href' => $href,
				'extra' => LD.$request->consent_name.RD,
				'selected' => ($highlight_id && $request->getId() == $highlight_id),
				'toolbar_items' => $toolbar,
				'selection' => [
					'name' => 'selection[]',
					'value' => $request->getId(),
					'data' => [
						'confirm' => lang('consent_request') . ': <b>' . ee('Format')->make('Text', $request->title)->convertToEntities() . '</b>'
					]
				]
			];

			if ( ! $request->user_created)
			{
				$datum['selection']['disabled'] = TRUE;
			}

			$data[] = $datum;
		}

		return [
			'requests'   => $data,
			'filters'    => $filters
		];
	}

	public function create()
	{
		ee()->cp->add_js_script('plugin', 'ee_url_title');
		ee()->javascript->set_global([
			'publish.word_separator' => ee()->config->item('word_separator') != "dash" ? '_' : '-',
		]);
		ee()->javascript->output('
			$("input[name=title]").bind("keyup keydown", function() {
				$(this).ee_url_title("input[name=consent_name]");
			});
		');

		return $this->form();
	}

	public function newVersion($request_id)
	{
		return $this->form($request_id);
	}

	public function remove()
	{
		$request_ids = ee()->input->post('selection');
		$requests = ee('Model')->get('ConsentRequest', $request_ids)
			->filter('user_created', 'y')
			->all();

		if ($requests->count() > 0 && ee()->input->post('bulk_action') == 'remove')
		{
			$requests->delete();

			ee('CP/Alert')->makeInline('user-alerts')
				->asSuccess()
				->withTitle(lang('consent_requests_removed'))
				->addToBody(sprintf(lang('consent_requests_removed_desc'), count($request_ids)))
				->defer();
		}
		else
		{
			show_error(lang('unauthorized_access'), 403);
		}

		ee()->functions->redirect(ee('CP/URL')->make('settings/consents', ee()->cp->get_url_state()));
	}

	public function versions($request_id)
	{
		$request = ee('Model')->get('ConsentRequest', $request_id)
			->first();

		if ( ! $request)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$table = ee('CP/Table');
		$table->setColumns(
			[
				'id' => [
					'type'	=> Table::COL_ID
				],
				'date',
				'author',
				'manage' => [
					'type'=> Table::COL_TOOLBAR
				],
			]
		);

		foreach ($request->Versions as $version)
		{
			$toolbar = [
				'toolbar_items' => [
					'view' => [
						'href' => '',
						'rel' => 'modal-consent-request-' . $version->getId(),
						'title' => strtolower(lang('view')),
						'class' => 'js-modal-link'
					]
				]
			];

			$data[] = [
				'id' => $version->getId(),
				'date' => ee()->localize->human_time($version->create_date->format('U')),
				'author' => ($version->Author) ? $version->Author->getMemberName() : lang('consent_app_created'),
				$toolbar
			];
		}

		$table->setNoResultsText('no_versions_found');
		$table->setData($data);

		$base_url = ee('CP/URL')->make('settings/consents/versions/' . $request->getId());

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $base_url->compile();
		$vars['versions'] = $request->Versions;

		ee()->view->base_url = $base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = sprintf(lang('consent_request_versions'), $request->title);
		ee()->cp->render('settings/consents/versions', $vars);
	}

	private function form($request_id = NULL)
	{
		if (is_null($request_id))
		{
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_consent_request');
			ee()->view->base_url = ee('CP/URL')->make('settings/consents/create');

			$request = ee('Model')->make('ConsentRequest');
			$request->user_created = TRUE;

			$version = $this->makeNewVersion($request);
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

			if (isset($_POST['request']) && $_POST['request'] != $request->CurrentVersion->request)
			{
				$version = $this->makeNewVersion($request);
			}
			else
			{
				$version = $request->CurrentVersion;
			}

			ee()->view->base_url = ee('CP/URL')->make('settings/consents/new_version/'.$request_id);

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_consent_request');
			ee()->view->breadcrumb_title = lang('edit').' '.$request->title;
		}

		$vars['errors'] = NULL;
		$alert_name = ($request->user_created) ? 'user-alerts' : 'app-alerts';

		if ( ! empty($_POST))
		{
			if ( ! $request->user_created)
			{
				unset($_POST['consent_name']);
			}

			$request->set($_POST);
			$version->set($_POST);
			$result = $request->validate();

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				// We need a request ID for the version, so we'll save when new.
				if ($request->isNew())
				{
					$request->save();
				}

				$version->save();

				// Ensure that whatever was submitted is marked as the current version.
				$request->CurrentVersion = $version;
				$request->save();

				if (is_null($request_id))
				{
					ee()->session->set_flashdata('highlight_id', $request->getId());
				}

				ee('CP/Alert')->makeInline($alert_name)
					->asSuccess()
					->withTitle(lang('consent_request_'.$alert_key))
					->addToBody(sprintf(lang('consent_request_'.$alert_key.'_desc'), $request->title))
					->defer();

				if (ee('Request')->post('submit') == 'save_and_new')
				{
					ee()->functions->redirect(ee('CP/URL')->make('settings/consents/create'));
				}
				elseif (ee()->input->post('submit') == 'save_and_close')
				{
					ee()->functions->redirect(ee('CP/URL')->make('settings/consents'));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL')->make('settings/consents/new_version/'.$request->getId()));
				}
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline($alert_name)
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
				ee('CP/Alert')->makeInline()
					->asWarning()
					->cannotClose()
					->withTitle(lang('important'))
					->addToBody(lang('new_consent_version_notice'))
					->addToBody('<b>'.lang('new_consent_version_destructive').'</b>')
					->render(),
				[
					'title' => 'consent_title',
					'fields' => [
						'title' => [
							'type' => 'text',
							'value' => $request->title,
							'required' => TRUE
						]
					]
				],
				[
					'title' => 'consent_name',
					'desc' => 'alphadash_desc',
					'fields' => [
						'consent_name' => [
							'type' => 'text',
							'value' => $request->consent_name,
							'required' => TRUE,
							'maxlength' => 50,
							'disabled' => ( ! $request->user_created)
						]
					]
				],
				[
					'title' => 'request',
					'fields' => [
						'request' => [
							'type' => 'textarea',
							'attrs' => ' class="textarea-tall" ',
							'value' => $version->request
						],
						'request_format' => [
							'type' => 'html',
							'content' => '<div class="format-options">' . form_dropdown('request_format', $format_options, [$version->request_format]) . '</div>',
							'margin_top' => FALSE
						]
					]
				]
			]
		];

		if ($request->isNew())
		{
			unset($vars['sections'][0][0]);
		}
		else
		{
			$modal = ee('View')->make('ee:settings/consents/destructive_modal')->render([]);
			ee('CP/Modal')->addModal('new-version', $modal);

			ee()->cp->add_js_script([
				'file' => [
					'cp/settings/consents/edit',
				],
			]);
		}

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

		ee()->view->ajax_validate = TRUE;

		ee()->cp->render('settings/form', $vars);
	}

	private function makeNewVersion(ConsentRequest $request = NULL)
	{
		$version = ee('Model')->make('ConsentRequestVersion');
		$version->author_id = ee()->session->userdata['member_id'];
		$version->create_date = ee()->localize->now;

		if ($request)
		{
			$request->Versions->add($version);

			if ($request->consent_request_version_id)
			{
				$version->request = $request->CurrentVersion->request;
				$version->request_format = $request->CurrentVersion->request_format;
			}
		}

		return $version;
	}
}
