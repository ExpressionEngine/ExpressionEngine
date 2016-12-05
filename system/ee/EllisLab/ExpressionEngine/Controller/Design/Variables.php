<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;


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
 * ExpressionEngine CP Design\Variables Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Variables extends AbstractDesignController {

	protected $msm = FALSE;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any('can_create_template_variables', 'can_edit_template_variables', 'can_delete_template_variables'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->generateSidebar('variables');
		$this->stdHeader();

		$this->msm = (ee()->config->item('multiple_sites_enabled') == 'y');
	}

	public function index()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL')->make('design/variables', ee()->cp->get_url_state()));
		}
		elseif (ee()->input->post('bulk_action') == 'export')
		{
			$this->exportVariables(ee()->input->post('selection'));
		}

		$vars = array();
		$table = ee('CP/Table', array('autosort' => TRUE));
		$columns = array(
			'variable',
			'all_sites' => array(
				'encode' => FALSE
			),
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		);

		if ( ! $this->msm)
		{
			unset($columns['all_sites']);
		}

		$variable_id = ee()->session->flashdata('variable_id');

		$table->setColumns($columns);

		$data = array();
		$variables = ee('Model')->make('GlobalVariable')->loadAll();

		$base_url = ee('CP/URL')->make('design/variables');

		foreach($variables as $variable)
		{
			if ($variable->site_id == 0)
			{
				$all_sites = '<b class="yes">' . lang('yes') . '</b>';
			}
			else
			{
				$all_sites = '<b class="no">' . lang('no') . '</b>';
			}
			$edit_url = ee('CP/URL')->make('design/variables/edit/' . $variable->variable_id);
			$column = array(
				array(
					'content' => $variable->variable_name,
					'href' => $edit_url
				),
				$all_sites,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					),
					'find' => array(
						'href' => ee('CP/URL')->make('design/template/search', array('search' => '{' . $variable->variable_name . '}')),
						'title' => lang('find')
					),
				)),
				array(
					'name' => 'selection[]',
					'value' => $variable->variable_id,
					'data'	=> array(
						'confirm' => lang('template_variable') . ': <b>' . htmlentities($variable->variable_name, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)

			);

			$attrs = array();

			if ($variable_id && $variable->variable_id == $variable_id)
			{
				$attrs = array('class' => 'selected');
			}

			if ( ! $this->msm)
			{
				unset($column[1]);
			}
			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);
		}

		$table->setNoResultsText('no_template_variables');
		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('template_variable') . ': <b>### ' . lang('template_variables') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('template_variables_header');
		ee()->cp->render('design/variables/index', $vars);
	}

	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_template_variables'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('design/variables/create'),
			'save_btn_text' => sprintf(lang('btn_save'), lang('template_variable')),
			'save_btn_text_working' => 'btn_create_template_variable_working',
			'sections' => array(
				array(
					array(
						'title' => 'variable_name',
						'fields' => array(
							'variable_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'variable_data',
						'wide' => TRUE,
						'fields' => array(
							'variable_data' => array(
								'type' => 'textarea',
								'required' => TRUE
							)
						)
					),
				)
			)
		);

		if ($this->msm)
		{
			$vars['sections'][0][] = array(
				'title' => 'enable_template_variable_on_all_sites',
				'desc' => 'enable_template_variable_on_all_sites_desc',
				'fields' => array(
					'site_id' => array(
						'type' => 'inline_radio',
						'choices' => array(
							'0' => 'enable',
							ee()->config->item('site_id') => 'disable'
						)
					)
				)
			);
		}
		else
		{
			$vars['form_hidden'] = array(
				'site_id' => ee()->config->item('site_id')
			);
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'variable_name',
				'label' => 'lang:variable_name',
				'rules' => 'required|callback__variable_name_checks'
			),
			array(
				'field' => 'variable_data',
				'label' => 'lang:variable_data',
				'rules' => 'required'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$variable = ee('Model')->make('GlobalVariable');
			$variable->site_id = ee()->input->post('site_id');
			$variable->variable_name = ee()->input->post('variable_name');
			$variable->variable_data = ee()->input->post('variable_data');
			$variable->save();

			ee()->session->set_flashdata('variable_id', $variable->variable_id);

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('create_template_variable_success'))
				->addToBody(sprintf(lang('create_template_variable_success_desc'), $variable->variable_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('design/variables'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_template_variable_error'))
				->addToBody(lang('create_template_variable_error_desc'))
				->now();
		}

		$this->loadCodeMirrorAssets('variable_data');

		ee()->view->cp_page_title = lang('create_template_variable');
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('design/variables')->compile() => lang('template_variables'),
		);

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($variable_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_template_variables'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$variable = ee('Model')->get('GlobalVariable')
			->filter('variable_id', $variable_id)
			->filterGroup()
				->filter('site_id', ee()->config->item('site_id'))
				->orFilter('site_id', 0)
			->endFilterGroup()
			->first();

		if ( ! $variable)
		{
			show_404();
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('design/variables/edit/' . $variable_id),
			'form_hidden' => array(
				'old_name' => $variable->variable_name
			),
			'save_btn_text' => sprintf(lang('btn_save'), lang('template_variable')),
			'save_btn_text_working' => 'btn_edit_template_variable_working',
			'sections' => array(
				array(
					array(
						'title' => 'variable_name',
						'fields' => array(
							'variable_name' => array(
								'type' => 'text',
								'required' => TRUE,
								'value' => $variable->variable_name
							)
						)
					),
					array(
						'title' => 'variable_data',
						'wide' => TRUE,
						'fields' => array(
							'variable_data' => array(
								'type' => 'textarea',
								'required' => TRUE,
								'value' => $variable->variable_data
							)
						)
					),
				)
			)
		);

		if ($this->msm)
		{
			$vars['sections'][0][] = array(
				'title' => 'enable_template_variable_on_all_sites',
				'desc' => 'enable_template_variable_on_all_sites_desc',
				'fields' => array(
					'site_id' => array(
						'type' => 'inline_radio',
						'choices' => array(
							'0' => 'enable',
							ee()->config->item('site_id') => 'disable'
						),
						'value' => $variable->site_id
					)
				)
			);
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'variable_name',
				'label' => 'lang:variable_name',
				'rules' => 'required|callback__variable_name_checks'
			),
			array(
				'field' => 'variable_data',
				'label' => 'lang:variable_data',
				'rules' => 'required'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			if ($this->msm)
			{
				$variable->site_id = ee()->input->post('site_id');
			}
			$variable->variable_name = ee()->input->post('variable_name');
			$variable->variable_data = ee()->input->post('variable_data');
			$variable->save();

			ee()->session->set_flashdata('variable_id', $variable->variable_id);

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_template_variable_success'))
				->addToBody(sprintf(lang('edit_template_variable_success_desc'), $variable->variable_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('design/variables'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('edit_template_variable_error'))
				->addToBody(lang('edit_template_variable_error_desc'))
				->now();
		}

		$this->loadCodeMirrorAssets('variable_data');

		ee()->view->cp_page_title = lang('edit_template_variable');
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('design/variables')->compile() => lang('template_variables'),
		);

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Removes variables
	 *
	 * @param  int|array $variable_ids The ids of variables to remove
	 * @return void
	 */
	private function remove($variable_ids)
	{
		if ( ! ee()->cp->allowed_group('can_delete_template_variables'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($variable_ids))
		{
			$variable_ids = array($variable_ids);
		}

		$variables = ee('Model')->get('GlobalVariable', $variable_ids)
			->filterGroup()
				->filter('site_id', ee()->config->item('site_id'))
				->orFilter('site_id', 0)
			->endFilterGroup()
			->all();

		$names = $variables->pluck('variable_name');

		$variables->delete();

		ee('CP/Alert')->makeInline('variable-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('template_variables_removed_desc'))
			->addToBody($names)
			->defer();
	}

	/**
	 * Export variables
	 *
	 * @param  int|array $variable_ids The ids of variables to export
	 * @return void
	 */
	private function exportVariables($variable_ids)
	{
		if ( ! is_array($variable_ids))
		{
			$variable_ids = array($variable_ids);
		}

		// Create the Zip Archive
		$zipfilename = tempnam(sys_get_temp_dir(), '');
		$zip = new ZipArchive();
		if ($zip->open($zipfilename, ZipArchive::CREATE) !== TRUE)
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('error_export'))
				->addToBody(lang('error_cannot_create_zip'))
				->now();
			return;
		}

		// Loop through variables and add them to the zip
		$variables = ee('Model')->get('GlobalVariable', $variable_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all()
			->each(function($variable) use($zip) {
				$zip->addFromString($variable->variable_name . '.html', $variable->variable_data);
			});

		$zip->close();

		$data = file_get_contents($zipfilename);
		unlink($zipfilename);

		ee()->load->helper('download');
		force_download('ExpressionEngine-template-variables.zip', $data);
	}

	/**
	  *	 Check GlobalVariable Name
	  */
	public function _variable_name_checks($str)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $str))
		{
			ee()->lang->loadfile('admin');
			ee()->form_validation->set_message('_variable_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		$reserved_vars = array(
			'lang',
			'charset',
			'homepage',
			'debug_mode',
			'gzip_mode',
			'version',
			'elapsed_time',
			'hits',
			'total_queries',
			'XID_HASH',
			'csrf_token'
		);

		if (in_array($str, $reserved_vars))
		{
			ee()->form_validation->set_message('_variable_name_checks', lang('reserved_name'));
			return FALSE;
		}

		$variables = ee('Model')->get('GlobalVariable');
		if ($this->msm)
		{
			$variables->filter('site_id', 'IN', array(0, ee()->config->item('site_id')));
		}
		else
		{
			$variables->filter('site_id', ee()->config->item('site_id'));
		}
		$count = $variables->filter('variable_name', $str)->count();

		if ((strtolower($this->input->post('old_name')) != strtolower($str)) AND $count > 0)
		{
			$this->form_validation->set_message('_variable_name_checks', lang('variable_name_taken'));
			return FALSE;
		}
		elseif ($count > 1)
		{
			$this->form_validation->set_message('_variable_name_checks', lang('variable_name_taken'));
			return FALSE;
		}

		return TRUE;
	}
}

// EOF
