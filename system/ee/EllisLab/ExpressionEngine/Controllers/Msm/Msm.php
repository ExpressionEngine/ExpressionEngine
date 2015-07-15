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

			if (count($sites) == 1)
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
						'is_system_on' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'online',
								'n' => 'offline'
							)
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

		if (ee('Model')->get('Sites')->all()->count() == count($site_ids))
		{
			show_error(lang('cannot_remove_all_sites'));
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
