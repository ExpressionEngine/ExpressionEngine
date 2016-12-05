<?php

namespace EllisLab\ExpressionEngine\Controller\Design;

use \EE_Route;
use ZipArchive;
use EllisLab\ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Model\Template\Template as TemplateModel;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

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
 * ExpressionEngine CP Design\Template Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Template extends AbstractDesignController {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->stdHeader();
	}

	public function create($group_name)
	{
		$errors = NULL;

		if ( ! ee()->cp->allowed_group('can_create_new_templates'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$group = ee('Model')->get('TemplateGroup')
			->filter('group_name', $group_name)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $group)
		{
			show_error(sprintf(lang('error_no_template_group'), $group_name));
		}

		if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$existing_templates = array(
			'0' => '-- ' . strtolower(lang('none')) . ' --'
		);

		$existing_templates = array_merge($existing_templates, $this->getExistingTemplates());

		$template = ee('Model')->make('Template');
		$template->site_id = ee()->config->item('site_id');
		$template->TemplateGroup = $group;

		// Duplicate a template?
		if (ee()->input->post('template_id'))
		{
			$master_template = ee('Model')->get('Template', ee()->input->post('template_id'))
				->first();

			$properties = $master_template->getValues();

			unset($properties['template_id']);
			unset($properties['site_id']);
			unset($properties['group_id']);
			unset($properties['hits']);

			$template->set($properties);
		}

		$result = $this->validateTemplate($template);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				// Unless we are duplicating a template the default is to
				// allow access to everyone
				if ( ! ee()->input->post('template_id'))
				{
					$template->NoAccess = NULL;
				}
				else
				{
					$template->NoAccess = $master_template->NoAccess;
				}

				$template->save();

				$alert = ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('create_template_success'))
					->addToBody(sprintf(lang('create_template_success_desc'), $group_name, $template->template_name))
					->defer();

				ee()->session->set_flashdata('template_id', $template->template_id);

				if (ee()->input->post('submit') == 'edit')
				{
					ee()->functions->redirect(ee('CP/URL', 'design/template/edit/' . $template->template_id));
				}
				else
				{
					ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
				}
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'base_url' => ee('CP/URL', 'design/template/create/' . $group_name),
			'sections' => array(
				array(
					array(
						'title' => 'name',
						'desc' => 'alphadash_desc',
						'fields' => array(
							'template_name' => array(
								'type' => 'text',
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'template_type',
						'fields' => array(
							'template_type' => array(
								'type' => 'select',
								'choices' => $this->getTemplateTypes()
							)
						)
					),
					array(
						'title' => 'duplicate_existing_template',
						'desc' => 'duplicate_existing_template_desc',
						'fields' => array(
							'template_id' => array(
								'type' => 'select',
								'choices' => $existing_templates
							)
						)
					),
				)
			),
			'buttons' => array(
				array(
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'finish',
					'text' => sprintf(lang('btn_save'), lang('template')),
					'working' => 'btn_saving'
				),
				array(
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'edit',
					'text' => 'btn_create_and_edit_template',
					'working' => 'btn_saving'
				),
			),
		);

		$this->generateSidebar($group->group_id);
		ee()->view->cp_page_title = lang('create_new_template');

		ee()->cp->render('settings/form', $vars);
	}

	public function edit($template_id)
	{
		$errors = NULL;

		if ( ! ee()->cp->allowed_group('can_edit_templates'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$template = ee('Model')->get('Template', $template_id)
			->with('TemplateGroup')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ($version_id = ee()->input->get('version'))
		{
			$version = ee('Model')->get('RevisionTracker', $version_id)->first();

			if ($version)
			{
				$template->template_data = $version->item_data;
			}
		}

		if ( ! $template)
		{
			show_error(lang('error_no_template'));
		}

		$group = $template->getTemplateGroup();

		if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$template_result = $this->validateTemplate($template);
		$route_result = $this->validateTemplateRoute($template);
		$result = $this->combineResults($template_result, $route_result);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if (AJAX_REQUEST && ($field = ee()->input->post('ee_fv_field')))
			{
				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
				exit;
			}

			if ($result->isValid())
			{
				$template->save();
				// Save a new revision
				$this->saveNewTemplateRevision($template);

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('update_template_success'))
					->addToBody(sprintf(lang('update_template_success_desc'), $group->group_name . '/' . $template->template_name))
					->defer();

				if (ee()->input->post('submit') == 'finish')
				{
					ee()->session->set_flashdata('template_id', $template->template_id);
					ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
				}

				ee()->functions->redirect(ee('CP/URL', 'design/template/edit/' . $template->template_id));
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'base_url' => ee('CP/URL', 'design/template/edit/' . $template_id),
			'tabs' => array(
				'edit' => $this->renderEditPartial($template, $errors),
				'notes' => $this->renderNotesPartial($template, $errors),
				'settings' => $this->renderSettingsPartial($template, $errors),
				'access' => $this->renderAccessPartial($template, $errors),
			),
			'buttons' => array(
				array(
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'edit',
					'text' => trim(sprintf(lang('btn_save'), '')),
					'working' => 'btn_saving'
				),
				array(
					'name' => 'submit',
					'type' => 'submit',
					'value' => 'finish',
					'text' => 'btn_save_and_close',
					'working' => 'btn_saving'
				),
			),
			'sections' => array(),
		);

		if (bool_config_item('save_tmpl_revisions'))
		{
			$vars['tabs']['revisions'] = $this->renderRevisionsPartial($template, $version_id);
		}

		$view_url = ee()->functions->fetch_site_index();
		$view_url = rtrim($view_url, '/').'/';

		if ($template->template_type == 'css')
		{
			$view_url .= QUERY_MARKER.'css='.$group->group_name.'/'.$template->template_name;
		}
		else
		{
			$view_url .= $group->group_name.(($template->template_name == 'index') ? '' : '/'.$template->template_name);
		}

		$this->stdHeader();
		$this->loadCodeMirrorAssets();

		ee()->view->cp_page_title = sprintf(lang('edit_template'), $group->group_name . '/' . $template->template_name);
		ee()->view->cp_page_title_alt = ee()->view->cp_page_title . ' <a class="btn action ta" href="' . ee()->cp->masked_url($view_url) . '" rel="external">' . lang('view_rendered') . '</a>';
		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('design')->compile() => lang('template_manager'),
			ee('CP/URL')->make('design/manager/' . $group->group_name)->compile() => sprintf(lang('breadcrumb_group'), $group->group_name)
		);

		// Supress browser XSS check that could cause obscure bug after saving
		ee()->output->set_header("X-XSS-Protection: 0");

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Renders the template revisions table for the Revisions tab
	 *
	 * @param TemplateModel $template A Template entity
	 * @param int $version_id ID of template version to mark as selected
	 * @return string Table HTML for insertion into Template edit form
	 */
	protected function renderRevisionsPartial($template, $version_id = FALSE)
	{
		if ( ! bool_config_item('save_tmpl_revisions'))
		{
			return FALSE;
		}

		$table = ee('CP/Table');

		$table->setColumns(
			array(
				'rev_id',
				'rev_date',
				'rev_author',
				'manage' => array(
					'encode' => FALSE
				)
			)
		);
		$table->setNoResultsText(lang('no_revisions'));

		$data = array();
		$i = 1;

		foreach ($template->Versions->sortBy('item_date') as $version)
		{
			$attrs = array();

			// Last item should be marked as current
			if ($template->Versions->count() == $i)
			{
				$toolbar = '<span class="st-open">' . lang('current') . '</span>';
			}
			else
			{
				$toolbar = ee('View')->make('_shared/toolbar')->render(array(
					'toolbar_items' => array(
							'txt-only' => array(
								'href' => ee('CP/URL')->make('design/template/edit/' . $template->getId(), array('version' => $version->getId())),
								'title' => lang('view'),
								'content' => lang('view')
							),
						)
					)
				);
			}

			// Mark currently-loaded version as selected
			if (( ! $version_id && $template->Versions->count() == $i) OR $version_id == $version->getId())
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'   => $attrs,
				'columns' => array(
					$i,
					ee()->localize->human_time($version->item_date),
					$version->getAuthorName(),
					$toolbar
				)
			);
			$i++;
		}

		$table->setData($data);

		return ee('View')->make('_shared/table')->render($table->viewData(''));
	}

	public function settings($template_id)
	{
		$errors = NULL;

		if ( ! ee()->cp->allowed_group('can_edit_templates'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$template = ee('Model')->get('Template', $template_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $template)
		{
			show_error(lang('error_no_template'));
		}

		$group = $template->getTemplateGroup();

		if ($this->hasEditTemplatePrivileges($group->group_id) === FALSE)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$template_result = $this->validateTemplate($template);
		$route_result = $this->validateTemplateRoute($template);
		$result = $this->combineResults($template_result, $route_result);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if (AJAX_REQUEST && ($field = ee()->input->post('ee_fv_field')))
			{
				if ($result->hasErrors($field))
				{
					ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
				}
				else
				{
					ee()->output->send_ajax_response('success');
				}
				exit;
			}

			if ($result->isValid())
			{
				$template->save();

				if (isset($_POST['save_modal']))
				{
					return array(
						'messageType' => 'success',
					);
				}

				$alert = ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('update_template_success'))
					->addToBody(sprintf(lang('update_template_success_desc'), $group->group_name . '/' . $template->template_name))
					->defer();

				ee()->session->set_flashdata('template_id', $template->template_id);
				ee()->functions->redirect(ee('CP/URL', 'design/manager/' . $group->group_name));
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'base_url' => ee('CP/URL', 'design/template/settings/' . $template_id),
			'tabs' => array(
				'settings' => $this->renderSettingsPartial($template, $errors),
				'access' => $this->renderAccessPartial($template, $errors),
			),
			'sections' => array(),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'cp_page_title' => lang('template_settings_and_access')
		);

		$html = ee()->cp->render('_shared/form', $vars, TRUE);

		if (isset($_POST['save_modal']))
		{
			return array(
				'messageType' => 'error',
				'body' => $html
			);
		}

		return $html;
	}

	public function search()
	{
		if (ee()->input->post('bulk_action') == 'export')
		{
			$this->exportTemplates(ee()->input->post('selection'));
		}

		$search_terms = ee()->input->get_post('search');

		$return = ee()->input->get_post('return');

		if ( ! $search_terms)
		{
			$return = ee('CP/URL')->decodeUrl($return);
		}
		else
		{
			$this->stdHeader($return);
		}

		$templates = ee('Model')->get('Template')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('template_data', 'LIKE', '%' . $search_terms . '%');

		if (ee()->session->userdata['group_id'] != 1)
		{
			$assigned_groups = array_keys(ee()->session->userdata['assigned_template_groups']);
			$templates->filter('group_id', 'IN', $assigned_groups);

			if (empty($assigned_groups))
			{
				$templates->markAsFutile();
			}
		}

		$templates = $templates->all();

		$base_url = ee('CP/URL')->make('design/template/search');

		$table = $this->buildTableFromTemplateCollection($templates, TRUE);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];
		$vars['show_new_template_button'] = FALSE;

		if ( ! empty($vars['table']['data']))
		{
			// Paginate!
			$vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
				->perPage($vars['table']['limit'])
				->currentPage($vars['table']['page'])
				->render($base_url);
		}

		ee()->view->cp_heading = sprintf(
			lang('search_results_heading'),
			$templates->count(),
			htmlentities($search_terms)
		);

		ee()->javascript->set_global('template_settings_url', ee('CP/URL')->make('design/template/settings/###')->compile());
		ee()->javascript->set_global('lang.remove_confirm', lang('template') . ': <b>### ' . lang('templates') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/design/manager'
			),
		));

		$this->generateSidebar();
		$this->stdHeader();
		ee()->view->cp_page_title = lang('template_manager');

		ee()->cp->render('design/index', $vars);
	}

	/**
	 * Sets a template entity with the POSTed data and validates it, setting
	 * an alert if there are any errors.
	 *
	 * @param TemplateModel $template A Template entity
	 * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
	 *  or a ValidationResult object.
	 */
	private function validateTemplate(TemplateModel $template)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$template->set($_POST);
		$template->edit_date = ee()->localize->now;
		$template->last_author_id = ee()->session->userdata('member_id');

		$result = $template->validate();

		$field = ee()->input->post('ee_fv_field');

		// The ajaxValidation method looks for the 'ee_fv_field' in the POST
		// data. Then it checks to see if the result object has an error
		// for that field. Then it'll return. Since we may be validating
		// a field on a TemplateRoute model we should check for that
		// befaore outputting an ajax response.
		if ( ! isset($_POST['save_modal'])
			&& isset($field)
			&& $template->hasProperty($field)
			&& $response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('update_template_error'))
				->addToBody(lang('update_template_error_desc'))
				->now();
		}
		else
		{
			$member_groups = ee('Model')->get('MemberGroup')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('group_id', '!=', 1)
				->all();

			$allowed_member_groups = ee()->input->post('allowed_member_groups') ?: array();

			$template->NoAccess = $member_groups->filter(function($group) use ($allowed_member_groups)
			{
				return ! in_array($group->group_id, $allowed_member_groups);
			});
		}

		return $result;
	}

	/**
	 * Sets a template route entity with the POSTed data and validates it,
	 * setting an alert if there are any errors.
	 *
	 * @param TemplateModel $template A Template entity
	 * @return mixed FALSE if nothing was posted, void if it was an AJAX call,
	 *  or a ValidationResult object.
	 */
	private function validateTemplateRoute(TemplateModel $template)
	{
		if (IS_CORE || ! ee()->input->post('route'))
		{
			$template->TemplateRoute = NULL;
			return FALSE;
		}

		if ( ! $template->TemplateRoute)
		{
			$template->TemplateRoute = ee('Model')->make('TemplateRoute');
		}

		$template->TemplateRoute->set($_POST);
		$result = $template->TemplateRoute->validate();

		if ( ! isset($_POST['save_modal']) && $response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('update_template_error'))
				->addToBody(lang('update_template_error_desc'))
				->now();
		}

		return $result;
	}

	/**
	 * Combines the results of two different model validation calls
	 *
	 * @param bool|ValidationResult $one FALSE (if nothing was submitted) or a
	 *   ValidationResult object.
	 * @param bool|ValidationResult $two FALSE (if nothing was submitted) or a
	 *   ValidationResult object.
	 * @return bool|ValidationResult $one FALSE (if nothing was submitted) or a
	 *   ValidationResult object.
	 */
	private function combineResults($one, $two)
	{
		$result = FALSE;

		if ($one instanceOf ValidationResult)
		{
			$result = $one;

			if ($two instanceOf ValidationResult && $two->failed())
			{
				foreach ($two->getFailed() as $field => $rules)
				{
					foreach ($rules as $rule)
					{
						$result->addFailed($field, $rule);
					}
				}
			}
		}
		elseif ($two instanceOf ValidationResult)
		{
			$result = $two;
		}

		return $result;
	}

	/**
	 * Get template types
	 *
	 * Returns a list of the standard EE template types to be used in
	 * template type selection dropdowns, optionally merged with
	 * user-defined template types via the template_types hook.
	 *
	 * @return array Array of available template types
	 */
	private function getTemplateTypes()
	{
		$template_types = array(
			'webpage'	=> lang('webpage'),
			'feed'		=> lang('rss'),
			'css'		=> lang('css_stylesheet'),
			'js'		=> lang('js'),
			'static'	=> lang('static'),
			'xml'		=> lang('xml')
		);

		// -------------------------------------------
		// 'template_types' hook.
		//  - Provide information for custom template types.
		//
		$custom_templates = ee()->extensions->call('template_types', array());
		//
		// -------------------------------------------

		if ($custom_templates != NULL)
		{
			// Instead of just merging the arrays, we need to get the
			// template_name value out of the associative array for
			// easy use of the form_dropdown helper
			foreach ($custom_templates as $key => $value)
			{
				$template_types[$key] = $value['template_name'];
			}
		}

		return $template_types;
	}

	/**
	 * Renders the portion of a form that contains the elements for editing
	 * a template's contents. This is especially useful for tabbed forms.
	 *
	 * @param TemplateModel $template A Template entity
	 * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
	 *   a ValidationResult object. This is needed to render any inline erorrs
	 *   on the form.
	 * @return string HTML
	 */
	private function renderEditPartial(TemplateModel $template, $errors)
	{
		$author = $template->getLastAuthor();

		$section = array(
			array(
				'title' => '',
				'desc' => sprintf(lang('last_edit'), ee()->localize->human_time($template->edit_date), (empty($author)) ? '-' : $author->screen_name),
				'wide' => TRUE,
				'fields' => array(
					'template_data' => array(
						'type' => 'textarea',
						'attrs' => 'class="template-edit"',
						'value' => $template->template_data,
					)
				)
			)
		);

		return ee('View')->make('_shared/form/section')
				->render(array('name' => NULL, 'settings' => $section, 'errors' => $errors));
	}

	/**
	 * Renders the portion of a form that contains the elements for editing
	 * a template's notes. This is especially useful for tabbed forms.
	 *
	 * @param TemplateModel $template A Template entity
	 * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
	 *   a ValidationResult object. This is needed to render any inline erorrs
	 *   on the form.
	 * @return string HTML
	 */
	private function renderNotesPartial(TemplateModel $template, $errors)
	{
		$section = array(
			array(
				'title' => 'template_notes',
				'desc' => 'template_notes_desc',
				'wide' => TRUE,
				'fields' => array(
					'template_notes' => array(
						'type' => 'textarea',
						'value' => $template->template_notes,
					)
				)
			)
		);

		return ee('View')->make('_shared/form/section')
				->render(array('name' => NULL, 'settings' => $section, 'errors' => $errors));
	}

	/**
	 * Renders the portion of a form that contains the elements for editing
	 * a template's settings. This is especially useful for tabbed forms.
	 *
	 * @param TemplateModel $template A Template entity
	 * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
	 *   a ValidationResult object. This is needed to render any inline erorrs
	 *   on the form.
	 * @return string HTML
	 */
	private function renderSettingsPartial(TemplateModel $template, $errors)
	{
		$sections = array(
			array(
				ee('CP/Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('php_in_templates_warning'))
					->addToBody(
						sprintf(lang('php_in_templates_warning2'), '<span title="excercise caution"></span>'),
						'caution'
					)
					->cannotClose()
					->render(),
				array(
					'title' => 'template_name',
					'desc' => 'alphadash_desc',
					'fields' => array(
						'old_name' => array(
							'type' => 'hidden',
							'value' => $template->template_name
						),
						'template_name' => array(
							'type' => 'text',
							'value' => $template->template_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'template_type',
					'fields' => array(
						'template_type' => array(
							'type' => 'select',
							'choices' => $this->getTemplateTypes(),
							'value' => $template->template_type
						)
					)
				),
				array(
					'title' => 'enable_caching',
					'desc' => 'enable_caching_desc',
					'fields' => array(
						'cache' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $template->cache
						)
					)
				),
				array(
					'title' => 'refresh_interval',
					'desc' => 'refresh_interval_desc',
					'fields' => array(
						'refresh' => array(
							'type' => 'text',
							'value' => $template->refresh
						)
					)
				),
				array(
					'title' => 'enable_php',
					'desc' => 'enable_php_desc',
					'caution' => TRUE,
					'fields' => array(
						'allow_php' => array(
							'type' => 'yes_no',
							'value' => $template->allow_php
						)
					)
				),
				array(
					'title' => 'parse_stage',
					'desc' => 'parse_stage_desc',
					'fields' => array(
						'php_parse_location' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'i' => 'input',
								'o' => 'output'
							),
							'value' => $template->php_parse_location
						)
					)
				),
				array(
					'title' => 'hit_counter',
					'desc' => 'hit_counter_desc',
					'fields' => array(
						'hits' => array(
							'type' => 'text',
							'value' => $template->hits
						)
					)
				)
			)
		);

		$html = '';

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	/**
	 * Renders the portion of a form that contains the elements for editing
	 * a template's access settings. This is especially useful for tabbed forms.
	 *
	 * @param TemplateModel $template A Template entity
	 * @param bool|ValidationResult $errors FALSE (if nothing was submitted) or
	 *   a ValidationResult object. This is needed to render any inline erorrs
	 *   on the form.
	 * @return string HTML
	 */
	private function renderAccessPartial(TemplateModel $template, $errors)
	{
		$existing_templates = $this->getExistingTemplates();

		$member_groups = ee('Model')->get('MemberGroup')
			->fields('group_id', 'group_title')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', 1)
			->all();

		$member_group_options = array_map(function($group_name) {
			return htmlentities($group_name, ENT_QUOTES, 'UTF-8');
		}, $member_groups->getDictionary('group_id', 'group_title'));

		$allowed_member_groups = array_diff(
			$member_groups->pluck('group_id'),
			$template->getNoAccess()->pluck('group_id')
		);

		$sections = array(
			array(
				array(
					'title' => 'allowed_member_groups',
					'desc' => 'allowed_member_groups_desc',
					'desc_cont' => 'allowed_member_groups_super_admin',
					'fields' => array(
						'allowed_member_groups' => array(
							'type' => 'checkbox',
							'wrap' => TRUE,
							'choices' => $member_group_options,
							'value' => $allowed_member_groups
						)
					)
				),
				array(
					'title' => 'no_access_redirect',
					'desc' => 'no_access_redirect_desc',
					'fields' => array(
						'no_auth_bounce' => array(
							'type' => 'select',
							'choices' => $existing_templates,
							'value' => $template->no_auth_bounce
						)
					)
				),
				array(
					'title' => 'enable_http_authentication',
					'desc' => 'enable_http_authentication_desc',
					'fields' => array(
						'enable_http_auth' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $template->enable_http_auth
						)
					)
				)
			)
		);

		if ( ! IS_CORE)
		{
			$route = $template->getTemplateRoute();

			if ( ! $route)
			{
				$route = ee('Model')->make('TemplateRoute');
			}

			$sections[0][] = array(
				'title' => 'template_route_override',
				'desc' => 'template_route_override_desc',
				'fields' => array(
					'route' => array(
						'type' => 'text',
						'value' => $route->route
					)
				)
			);
			$sections[0][] = array(
				'title' => 'require_all_segments',
				'desc' => 'require_all_segments_desc',
				'fields' => array(
					'route_required' => array(
						'type' => 'yes_no',
						'value' => $route->route_required
					)
				)
			);
		}

		$html = '';

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	/**
	 * Gets a list of all the templates for the current site, grouped by
	 * their template group name:
	 *   array(
	 *     'news' => array(
	 *       1 => 'index',
	 *       3 => 'about',
	 *     )
	 *   )
	 *
	 * @return array An associative array of templates
	 */
	private function getExistingTemplates()
	{
		$existing_templates = array();

		$all_templates = ee('Model')->get('Template')
			->filter('site_id', ee()->config->item('site_id'))
			->with('TemplateGroup')
			->order('TemplateGroup.group_name')
			->order('template_name')
			->all();

		foreach ($all_templates as $template)
		{
			if ( ! isset($existing_templates[$template->TemplateGroup->group_name]))
			{
				$existing_templates[$template->TemplateGroup->group_name] = array();
			}
			$existing_templates[$template->TemplateGroup->group_name][$template->template_id] = $template->template_name;
		}

		return $existing_templates;
	}
}

// EOF
