<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Design;

use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Design\Snippets Controller
 */
class Snippets extends AbstractDesignController {

	protected $msm = FALSE;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any('can_create_template_partials', 'can_edit_template_partials', 'can_delete_template_partials'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$this->generateSidebar('partials');
		$this->stdHeader();

		$this->msm = (ee()->config->item('multiple_sites_enabled') == 'y');

		// make sure all partials are synced from the filesystem
		ee('Model')->make('Snippet')->loadAll();
	}

	public function index()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->remove(ee()->input->post('selection'));
			ee()->functions->redirect(ee('CP/URL')->make('design/snippets', ee()->cp->get_url_state()));
		}
		elseif (ee()->input->post('bulk_action') == 'export')
		{
			$this->exportSnippets(ee()->input->post('selection'));
		}

		$vars = array();
		$table = ee('CP/Table', array('autosort' => FALSE));
		$columns = array(
			'partial',
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

		$snippet_id = ee()->session->flashdata('snippet_id');

		$table->setColumns($columns);

		$data = array();
		$snippets = ee('Model')->get('Snippet')
			->filter('site_id', 'IN', array(0, ee()->config->item('site_id')));

		$this->base_url = ee('CP/URL')->make('design/snippets');

		$total = $snippets->count();

		$filters = ee('CP/Filter')
			->add('Keyword')
			->add('Perpage', $total, 'show_all_partials');

		// Before pagination so perpage is set correctly
		$this->renderFilters($filters);

		$sort_col = $table->sort_col;

		$sort_map = array(
			'all_sites' => 'site_id',
			'partial' => 'snippet_name'
		);

		if ( ! array_key_exists($sort_col, $sort_map))
		{
			throw new \Exception("Invalid sort column: ".htmlentities($sort_col));
		}

		$snippet_data = $snippets->order($sort_map[$sort_col], $table->sort_dir)
			->limit($this->perpage)
			->offset($this->offset);

		if (isset($this->params['filter_by_keyword']))
		{
			$snippet_data->search(['snippet_name', 'snippet_contents'], $this->params['filter_by_keyword']);
		}

		$snippet_data = $snippet_data->all();

		foreach ($snippet_data as $snippet)
		{
			if ($snippet->site_id == 0)
			{
				$all_sites = '<b class="yes">' . lang('yes') . '</b>';
			}
			else
			{
				$all_sites = '<b class="no">' . lang('no') . '</b>';
			}
			$edit_url = ee('CP/URL')->make('design/snippets/edit/' . $snippet->snippet_id);
			$column = array(
				array(
					'content' => $snippet->snippet_name,
					'href' => $edit_url
				),
				$all_sites,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					),
					'find' => array(
						'href' => ee('CP/URL')->make('design/template/search', array('search' => '{' . $snippet->snippet_name . '}')),
						'title' => lang('find')
					),
				)),
				array(
					'name' => 'selection[]',
					'value' => $snippet->snippet_id,
					'data'	=> array(
						'confirm' => lang('template_partial') . ': <b>' . htmlentities($snippet->snippet_name, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();

			if ($snippet_id && $snippet->snippet_id == $snippet_id)
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

		$table->setNoResultsText('no_snippets');
		$table->setData($data);

		$vars['table'] = $table->viewData($this->base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $total)
				->perPage($this->perpage)
				->currentPage($this->page)
				->render($this->base_url);
		}

		ee()->javascript->set_global('lang.remove_confirm', lang('template_partial') . ': <b>### ' . lang('template_partials') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');
		ee()->view->cp_heading = lang('template_partials_header');
		ee()->cp->render('design/snippets/index', $vars);
	}

	public function create()
	{
		if ( ! ee()->cp->allowed_group('can_create_template_partials'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('design/snippets/create'),
			'save_btn_text' => sprintf(lang('btn_save'), lang('partial')),
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'snippet_name',
						'fields' => array(
							'snippet_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'snippet_contents',
						'wide' => TRUE,
						'fields' => array(
							'snippet_contents' => array(
								'type' => 'textarea',
								'attrs' => 'class="textarea-medium"'
							)
						)
					),
				)
			)
		);

		if ($this->msm)
		{
			$vars['sections'][0][] = array(
				'title' => 'enable_partial_on_all_sites',
				'desc' => 'enable_partial_on_all_sites_desc',
				'fields' => array(
					'site_id' => array(
						'type' => 'inline_radio',
						'choices' => array(
							'0' => 'all_sites',
							ee()->config->item('site_id') => ee()->config->item('site_label').' '.lang('only')
						),
						'encode' => FALSE,
						'value' => '0',
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
				'field' => 'snippet_name',
				'label' => 'lang:snippet_name',
				'rules' => 'required|max_length[50]|callback__snippet_name_checks'
			)
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$snippet = ee('Model')->make('Snippet');
			$snippet->site_id = ee()->input->post('site_id');
			$snippet->snippet_name = ee()->input->post('snippet_name');
			$snippet->snippet_contents = ee()->input->post('snippet_contents');
			$snippet->save();

			ee()->session->set_flashdata('snippet_id', $snippet->snippet_id);

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('create_template_partial_success'))
				->addToBody(sprintf(lang('create_template_partial_success_desc'), $snippet->snippet_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('design/snippets'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_template_partial_error'))
				->addToBody(lang('create_template_partial_error_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('create_partial');
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('design/snippets')->compile() => lang('template_partials'),
		);

		$this->loadCodeMirrorAssets('snippet_contents');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($snippet_id)
	{
		if ( ! ee()->cp->allowed_group('can_edit_template_partials'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$snippet = ee('Model')->get('Snippet')
			->filter('snippet_id', $snippet_id)
			->filterGroup()
				->filter('site_id', ee()->config->item('site_id'))
				->orFilter('site_id', 0)
			->endFilterGroup()
			->first();

		if ( ! $snippet)
		{
			show_404();
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('design/snippets/edit/' . $snippet_id),
			'form_hidden' => array(
				'old_name' => $snippet->snippet_name
			),
			'save_btn_text' => sprintf(lang('btn_save'), lang('partial')),
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'snippet_name',
						'fields' => array(
							'snippet_name' => array(
								'type' => 'text',
								'required' => TRUE,
								'value' => $snippet->snippet_name
							)
						)
					),
					array(
						'title' => 'snippet_contents',
						'wide' => TRUE,
						'fields' => array(
							'snippet_contents' => array(
								'type' => 'textarea',
								'attrs' => 'class="textarea-medium"',
								'value' => $snippet->snippet_contents
							)
						)
					),
				)
			)
		);

		if ($this->msm)
		{
			$vars['sections'][0][] = array(
				'title' => 'enable_partial_on_all_sites',
				'desc' => 'enable_partial_on_all_sites_desc',
				'fields' => array(
					'site_id' => array(
						'type' => 'inline_radio',
						'choices' => array(
							'0' => 'all_sites',
							ee()->config->item('site_id') => ee()->config->item('site_label').' '.lang('only')
						),
						'value' => $snippet->site_id,
						'encode' => FALSE
					)
				)
			);
		}

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'snippet_name',
				'label' => 'lang:snippet_name',
				'rules' => 'required|max_length[50]|callback__snippet_name_checks'
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
				$snippet->site_id = ee()->input->post('site_id');
			}
			$snippet->snippet_name = ee()->input->post('snippet_name');
			$snippet->snippet_contents = ee()->input->post('snippet_contents');
			$snippet->save();

			ee()->session->set_flashdata('snippet_id', $snippet->snippet_id);

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_template_partial_success'))
				->addToBody(sprintf(lang('edit_template_partial_success_desc'), $snippet->snippet_name))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('design/snippets'));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('edit_template_partial_error'))
				->addToBody(lang('edit_template_partial_error_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('edit_partial');
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('design/snippets')->compile() => lang('template_partials'),
		);

		$this->loadCodeMirrorAssets('snippet_contents');

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Removes snippets
	 *
	 * @param  int|array $snippet_ids The ids of snippets to remove
	 * @return void
	 */
	private function remove($snippet_ids)
	{
		if ( ! ee()->cp->allowed_group('can_delete_template_partials'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		if ( ! is_array($snippet_ids))
		{
			$snippet_ids = array($snippet_ids);
		}

		$snippets = ee('Model')->get('Snippet', $snippet_ids)
			->filterGroup()
				->filter('site_id', ee()->config->item('site_id'))
				->orFilter('site_id', 0)
			->endFilterGroup()
			->all();

		$names = $snippets->pluck('snippet_name');

		$snippets->delete();

		ee('CP/Alert')->makeInline('snippet-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('snippets_removed_desc'))
			->addToBody($names)
			->defer();
	}

	/**
	 * Export snippets
	 *
	 * @param  int|array $snippet_ids The ids of snippets to export
	 * @return void
	 */
	private function exportSnippets($snippet_ids)
	{
		if ( ! is_array($snippet_ids))
		{
			$snippet_ids = array($snippet_ids);
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

		// Loop through snippets and add them to the zip
		$snippets = ee('Model')->get('Snippet', $snippet_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all()
			->each(function($snippet) use($zip) {
				$zip->addFromString($snippet->snippet_name . '.html', $snippet->snippet_contents);
			});

		$zip->close();

		$data = file_get_contents($zipfilename);
		unlink($zipfilename);

		ee()->load->helper('download');
		force_download('ExpressionEngine-template-partials.zip', $data);
	}

	/**
	  *	 Check Snippet Name
	  */
	public function _snippet_name_checks($str)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $str))
		{
			ee()->lang->loadfile('admin');
			ee()->form_validation->set_message('_snippet_name_checks', lang('illegal_characters'));
			return FALSE;
		}

		if (in_array($str, ee()->cp->invalid_custom_field_names()))
		{
			ee()->form_validation->set_message('_snippet_name_checks', lang('reserved_name'));
			return FALSE;
		}

		$snippets = ee('Model')->get('Snippet');
		if ($this->msm)
		{
			$snippets->filter('site_id', 'IN', [ee()->config->item('site_id'), 0]);
		}
		else
		{
			$snippets->filter('site_id', ee()->config->item('site_id'));
		}
		$count = $snippets->filter('snippet_name', $str)->count();

		if ((strtolower($this->input->post('old_name')) != strtolower($str)) AND $count > 0)
		{
			$this->form_validation->set_message('_snippet_name_checks', lang('snippet_name_taken'));
			return FALSE;
		}
		elseif ($count > 1)
		{
			$this->form_validation->set_message('_snippet_name_checks', lang('snippet_name_taken'));
			return FALSE;
		}

		return TRUE;
	}
}

// EOF
