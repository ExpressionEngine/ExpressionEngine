<?php

namespace EllisLab\ExpressionEngine\Controllers\Msm;

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Multiple Site Manager Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Msm extends CP_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if (ee()->config->item('multiple_sites_enabled') !== 'y')
        {
			show_404();
        }

		ee()->lang->loadfile('sites');

		$this->stdHeader();
		$this->sidebarMenu();
	}

	protected function sidebarMenu($active = NULL)
	{
		$sites = array();

		foreach (ee('Model')->get('Site')->order('site_label', 'asc')->all() as $site)
		{
			$sites[$site->site_label] = ee('CP/URL', 'msm/switch/' . $site->site_id);
		}

		$menu = array(
			'sites' => array(
				'href' => ee('CP/URL', 'msm'),
				'button' => array(
					'href' => ee('CP/URL', 'msm/create'),
					'text' => 'new',
				)
			),
			'switch_to',
			$sites
		);

		ee()->menu->register_left_nav($menu);
	}

	protected function stdHeader()
	{
		ee()->view->header = array(
			'title' => lang('msm_manager'),
			'form_url' => ee('CP/URL', 'msm'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => ee('CP/URL', 'settings/general'),
					'title' => lang('settings')
				)
			),
			'search_button_value' => lang('search')
		);
	}

	public function index()
	{
		if ( count(ee()->session->userdata('assigned_sites')) == 0 )
		{
			show_error(lang('unauthorized_access'));
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL', 'msm'));
		}

		$base_url = ee('CP/URL', 'msm');

		$vars['create_url'] = ee('CP/URL', 'msm/create');

		$sites = ee('Model')->get('Site')->all();

		$table = ee('CP/Table', array('autosort' => TRUE, 'autosearch' => TRUE));
		$table->setColumns(
			array(
				'col_id',
				'name',
				'short_name' => array(
					'encode' => FALSE
				),
				'status' => array(
					'type' => Table::COL_STATUS
				),
				'manage' => array(
					'type' => Table::COL_TOOLBAR
				),
				array(
					'type' => Table::COL_CHECKBOX
				)
			)
		);

		$data = array();

		$site_id = ee()->session->flashdata('site_id');

		foreach ($sites as $site)
		{
			if ($site->site_system_preferences->is_site_on == 'y')
			{
				$status = array(
					'class' => 'enable',
					'content' => lang('online')
				);
			}
			else
			{
				$status = array(
					'class' => 'disable',
					'content' => lang('offline')
				);
			}
			$column = array(
				$site->site_id,
				$site->site_label,
				'<var>{' . htmlentities($site->site_name, ENT_QUOTES) . '}</var>',
				$status,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => ee('CP/URL', 'msm/edit/' . $site->site_id),
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'selection[]',
					'value' => $site->site_id,
					'data' => array(
						'confirm' => lang('site') . ': <b>' . htmlentities($site->site_label, ENT_QUOTES) . '</b>'
					)
				)
			);

			if ($site->site_id == 1)
			{
				$column[5]['disabled'] = TRUE;
			}

			$attrs = array();

			if ($site_id && $site->site_id == $site_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);

		$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('site') . ': <b>### ' . lang('sites') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/v3/confirm_remove',
			),
		));

		ee()->view->cp_page_title = lang('sites');

		ee()->cp->render('msm/index', $vars);
	}

	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_admin_sites'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'msm')->compile() => lang('msm_manager'),
		);

		if ( ! empty($_POST))
		{
			$site = ee('Model')->make('Site', $_POST);
			$site->site_bootstrap_checksums = array();
			$site->site_pages = array();
			$result = $site->validate();

			if ($response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				foreach(array('system', 'channel', 'template', 'mailinglist', 'member') as $type)
				{
					$prefs = 'site_' . $type . '_preferences';

					foreach(ee()->config->divination($type) as $value)
					{
						$site->$prefs->$value = ee()->config->item($value);
					}
				}

				$site->site_template_preferences->save_tmpl_files = 'n';
				$site->site_template_preferences->tmpl_file_basepath = '';

				$site->save();

				// Create new site-specific stats by cloning site 1
				$data = ee('Model')->get('Stats')
					->filter('site_id', 1)
					->first()
					->getValues();

				unset($data['stat_id']);
				$data['site_id'] = $site->site_id;
				$data['last_entry_date'] = 0;
				$data['last_cache_clear'] = 0;

				ee('Model')->make('Stats', $data)->save();

				// Create new site-specific HTML buttons
				$buttons = ee('Model')->get('HTMLButton')
					->filter('site_id', 1)
					->filter('member_id', 1)
					->all();

				foreach($buttons as $button)
				{
					$data = $button->getValues();
					unset($data['id']);
					$data['site_id'] = $site->site_id;

					ee('Model')->make('HTMLButton', $data)->save();
				}

				// Create new site-specific specialty templates
				$templates = ee('Model')->get('SpecialtyTemplate')
					->filter('site_id', 1)
					->all();

				foreach($templates as $template)
				{
					$data = $template->getValues();
					unset($data['template_id']);
					$data['site_id'] = $site->site_id;

					ee('Model')->make('SpecialtyTemplate', $data)->save();
				}

				// Create new site-specific member groups
				// Not working yet -sb
				// $groups = ee('Model')->get('MemberGroup')
				// 	->filter('site_id', 1)
				// 	->all();
				//
				// foreach($groups as $group)
				// {
				// 	$data = $group->getValues();
				// 	$data['site_id'] = $site->site_id;
				//
				// 	ee('Model')->make('MemberGroup', $data)->save();
				// }

				ee()->session->set_flashdata('site_id', $site->site_id);

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('create_site_success'))
					->addToBody(sprintf(lang('create_site_success_desc'), $site->site_label))
					->defer();

				ee()->functions->redirect(ee('CP/URL', 'msm'));
			}
			else
			{
				ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('create_site_error'))
					->addToBody(lang('create_site_error_desc'))
					->now();
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL', 'msm/create'),
			'save_btn_text' => 'btn_create_site',
			'save_btn_text_working' => 'btn_saving',
		);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'name_desc',
					'fields' => array(
						'site_label' => array(
							'type' => 'text',
							'value' => '',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'short_name_desc',
					'fields' => array(
						'site_name' => array(
							'type' => 'text',
							'value' => '',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'description',
					'desc' => 'description_desc',
					'fields' => array(
						'site_description' => array(
							'type' => 'textarea',
						)
					)
				),
			)
		);

		ee()->view->cp_page_title = lang('create_site');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($site_id)
	{
		if ( ! ee()->cp->allowed_group('can_admin_sites'))
		{
			show_error(lang('unauthorized_access'));
		}

		$site = ee('Model')->get('Site', $site_id)->first();

		if ( ! $site)
		{
			show_404();
		}

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL', 'msm')->compile() => lang('msm_manager'),
		);

		if ( ! empty($_POST))
		{
			$site->set($_POST);
			$site->site_system_preferences->is_site_on = ee()->input->post('is_site_on');
			$result = $site->validate();

			if ($response = $this->ajaxValidation($result))
			{
			    return $response;
			}

			if ($result->isValid())
			{
				$site->save();

				ee('Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('edit_site_success'))
					->addToBody(sprintf(lang('edit_site_success_desc'), $site->site_label))
					->defer();

				ee()->functions->redirect(ee('CP/URL', 'msm/edit/' . $site_id));
			}
			else
			{
				ee('Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('edit_site_error'))
					->addToBody(lang('edit_site_error_desc'))
					->now();
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL', 'msm/edit/' . $site_id),
			'save_btn_text' => 'btn_edit_site',
			'save_btn_text_working' => 'btn_saving',
		);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'name_desc',
					'fields' => array(
						'site_label' => array(
							'type' => 'text',
							'value' => $site->site_label,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'short_name_desc',
					'fields' => array(
						'site_name' => array(
							'type' => 'text',
							'value' => $site->site_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'site_online',
					'desc' => 'site_online_desc',
					'fields' => array(
						'is_site_on' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'online',
								'n' => 'offline'
							),
							'value' => $site->site_system_preferences->is_site_on
						)
					)
				),
				array(
					'title' => 'description',
					'desc' => 'description_desc',
					'fields' => array(
						'site_description' => array(
							'type' => 'textarea',
							'value' => $site->site_description,
						)
					)
				),
			)
		);

		ee()->view->cp_page_title = lang('edit_site');

		ee()->cp->render('settings/form', $vars);
	}

	private function remove($site_ids)
	{
		if ( ! is_array($site_ids))
		{
			$site_ids = array($site_ids);
		}

		if (in_array(1, $site_ids))
		{
			$site = ee('Model')->get('Site', 1)
				->fields('site_label')
				->first();
			show_error(sprintf(lang('cannot_remove_site_1'), $site->site_label));
		}

		$sites = ee('Model')->get('Site', $site_ids)->all();

		$site_names = $sites->pluck('site_label');

		$sites->delete();
		ee('Alert')->makeInline('sites')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('sites_removed_desc'))
			->addToBody($site_names)
			->defer();
	}
}
// EOF
