<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;
use EllisLab\ExpressionEngine\Library\CP\Table;

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

// --------------------------------------------------------------------

/**
 * ExpressionEngine Discussion Forum Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Forum_mcp extends CP_Controller {

	public $base = 'addons/settings/forum/';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		ee()->lang->loadfile('forum_cp');

		// Garbage collection.  Delete old read topic data
		$year_ago = ee()->localize->now - (60*60*24*365);
		ee()->db->where('last_visit <', $year_ago);
		ee()->db->delete('forum_read_topics');
	}

	private function generateSidebar($active = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		$boards = $sidebar->addHeader(lang('forum_boards'))
			->withButton(lang('new'), ee('CP/URL')->make($this->base . 'create/board'));

		$board_list = $boards->addFolderList('boards')
			->withRemoveUrl(ee('CP/URL')->make($this->base . 'remove/board', array('return' => ee('CP/URL')->getCurrentUrl()->encode())))
			->withNoResultsText(sprintf(lang('no_found'), lang('forum_boards')));

		$all_boards = ee('Model')->get('forum:Board')
			->fields('board_id', 'board_label')
			->filter('board_alias_id', 0)
			->all();

		if (count($all_boards))
		{
			foreach ($all_boards as $board)
			{
				$item = $board_list->addItem($board->board_label, ee('CP/URL')->make($this->base . 'index/' . $board->board_id))
					->withEditUrl(ee('CP/URL')->make($this->base . 'edit/board/' . $board->board_id))
					->withRemoveConfirmation(lang('forum_board') . ': <b>' . $board->board_label . '</b>')
					->identifiedBy($board->board_id);

				if ($board->board_id == $active)
				{
					$item->isActive();
				}
			}
		}

		$aliases = $sidebar->addHeader(lang('forum_aliases'))
			->withButton(lang('new'), ee('CP/URL')->make($this->base . 'create/alias'));

		$alias_list = $aliases->addFolderList('aliases')
			->withRemoveUrl(ee('CP/URL')->make($this->base . 'remove/alias', array('return' => ee('CP/URL')->getCurrentUrl()->encode())))
			->withNoResultsText(sprintf(lang('no_found'), lang('forum_aliases')));

		$all_aliases = ee('Model')->get('forum:Board')
			->fields('board_id', 'board_alias_id', 'board_label')
			->filter('board_alias_id', '>', 0)
			->all();

		if (count($all_aliases))
		{
			foreach ($all_aliases as $alias)
			{
				$item = $alias_list->addItem($alias->board_label, ee('CP/URL')->make($this->base . 'index/' . $alias->board_alias_id))
					->withEditUrl(ee('CP/URL')->make($this->base . 'edit/alias/' . $alias->board_id))
					->withRemoveConfirmation(lang('forum_alias') . ': <b>' . $alias->board_label . '</b>')
					->identifiedBy($alias->board_id)
					->isInactive();
			}
		}

		$sidebar->addHeader(lang('templates'))
			->withUrl(ee('CP/URL')->make('design/forums'));

		$ranks = $sidebar->addHeader(lang('member_ranks'))
			->withUrl(ee('CP/URL')->make($this->base . 'ranks'));

		if ($active == 'ranks')
		{
			$ranks->isActive();
		}

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/addons/forums/sidebar',
			),
		));
	}

	private function getStatusWidget($status)
	{
		$html = '';

		switch ($status)
		{
			case 'o': $html = '<b class="yes">' . lang('live') . '</b>'; break;
			case 'c': $html = '<b class="no">' . lang('hidden') . '</b>'; break;
			case 'a': $html = '<i>' . lang('read_only') . '</i>'; break;
		}

		return strtolower($html);
	}

	/**
	 * Forum Home Page
	 */
	public function index($id = NULL)
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->removeForums(ee()->input->post('selection'));
		}

		$board = ee('Model')->get('forum:Board', $id)
			->order('board_id', 'asc')
			->first();

		$categories = array();

		if ($board)
		{
			$id = $board->board_id; // in case $id was NULL
			$forum_id = ee()->session->flashdata('forum_id');

			$boards_categories = ee('Model')->get('forum:Forum')
				->filter('board_id', $id)
				->filter('forum_is_cat', 'y')
				->order('forum_order', 'asc')
				->all();

			foreach ($boards_categories as $i => $category)
			{
				$manage = array(
					'toolbar_items' => array(
						'edit' => array(
							'href' => ee('CP/URL')->make($this->base . 'edit/category/' . $category->forum_id),
							'title' => lang('edit'),
						),
						'settings' => array(
							'href' => ee('CP/URL')->make($this->base . 'settings/category/' . $category->forum_id),
							'title' => lang('settings'),
						)
					)
				);
				$manage = ee('View')->make('ee:_shared/toolbar')->render($manage);

				$class = ($i == count($boards_categories) - 1) ? '' : 'mb';

				$table_config = array(
					'limit'             => 0,
					'reorder'           => TRUE,
					'reorder_header'    => TRUE,
					'sortable'          => FALSE,
					'class'             => $class,
					'wrap'              => FALSE,
				);

				$table = ee('CP/Table', $table_config);
				$table->setColumns(
					array(
						$category->forum_name.form_hidden('cat_order[]', $category->forum_id) => array(
							'encode' => FALSE
						),
						$this->getStatusWidget($category->forum_status) => array(
							'encode' => FALSE
						),
						$manage => array(
							'type'	=> Table::COL_TOOLBAR,
						),
						array(
							'type'	=> Table::COL_CHECKBOX
						)
					)
				);
				$table->setNoResultsText(sprintf(lang('no_found'), lang('forums')), 'create_new_forum', ee('CP/URL')->make($this->base . 'create/forum/' . $category->forum_id));
				$table->addActionButton(ee('CP/URL')->make($this->base . 'create/forum/' . $category->forum_id), lang('new_forum'));

				$data = array();
				foreach ($category->Forums->sortBy('forum_order') as $forum)
				{
					$edit_url = ee('CP/URL')->make($this->base . 'edit/forum/' . $forum->forum_id);

					$row = array(
						'<a href="' . $edit_url . '">' . $forum->forum_name . '</a>' . form_hidden('order[]', $forum->forum_id),
						$this->getStatusWidget($forum->forum_status),
						array('toolbar_items' => array(
								'edit' => array(
									'href' => $edit_url,
									'title' => lang('edit'),
								),
								'settings' => array(
									'href' => ee('CP/URL')->make($this->base . 'settings/forum/' . $forum->forum_id),
									'title' => lang('settings'),
								)
							)
						),
						array(
							'name' => 'selection[]',
							'value' => $forum->forum_id,
							'data'	=> array(
								'confirm' => lang('forum') . ': <b>' . htmlentities($forum->forum_name, ENT_QUOTES, 'UTF-8') . '</b>'
							)
						)
					);

					$attrs = array();

					if ($forum_id && $forum->forum_id == $forum_id)
					{
						$attrs = array('class' => 'selected');
					}

					$data[] = array(
						'attrs'		=> $attrs,
						'columns'	=> $row
					);
				}
				$table->setData($data);
				$categories[] = $table->viewData(ee('CP/URL')->make($this->base . 'index/' . $id));
			}

		}

		$vars = array(
			'board' => $board,
			'categories' => $categories,
		);

		$body = ee('View')->make('forum:index')->render($vars);

		ee()->javascript->set_global('lang.remove_confirm', lang('forum') . ': <b>### ' . lang('forums') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
				'cp/addons/forums/reorder',
			),
			'plugin' => array(
				'ee_table_reorder',
			),
		));

		$reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
			->asIssue()
			->canClose()
			->withTitle(lang('forums_ajax_reorder_fail'))
			->addToBody(lang('forums_ajax_reorder_fail_desc'));

		ee()->javascript->set_global('forums.reorder_url', ee('CP/URL')->make($this->base . 'reorder/' . $id)->compile());
		ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

		$this->generateSidebar($id);

		return array(
			'body'    => $body,
			'heading' => lang('forum_manager'),
		);
	}

	public function reorder($id)
	{
		$board = ee('Model')->get('forum:Board', $id)->first();

		// Parse out the serialized inputs sent by the JavaScript
		$new_order = array();
		parse_str(ee()->input->post('order'), $new_order);

		if ( ! AJAX_REQUEST OR ! $board OR (empty($new_order['order']) && empty($new_order['cat_order'])))
		{
			show_error(lang('unauthorized_access'));
		}

		if (isset($new_order['order']))
		{
			$order = $new_order['order'];
			$collection = $board->Forums->indexBy('forum_id');
		}
		else
		{
			$order = $new_order['cat_order'];
			$collection = $board->Categories->indexBy('forum_id');
		}

		$i = 1;
		foreach ($order as $forum_id)
		{
			// Only update status orders that have changed
			if (isset($collection[$forum_id]) && $collection[$forum_id]->forum_order != $i)
			{
				$collection[$forum_id]->forum_order = $i;
				$collection[$forum_id]->save();
			}

			$i++;
		}

		ee()->output->send_ajax_response(NULL);
		exit;
	}

	/**
	 * Dispatch method for the various things that can be created
	 */
	public function create($type)
	{
		$parameters = array_slice(func_get_args(), 1);
		$method = 'create' . ucfirst($type);

		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $parameters);
		}

		show_404();
	}

	/**
	 * Dispatch method for the various things that can be edit
	 */
	public function edit($type)
	{
		$parameters = array_slice(func_get_args(), 1);
		$method = 'edit' . ucfirst($type);

		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $parameters);
		}

		show_404();
	}

	/**
	 * Dispatch method for the various things that can be edit
	 */
	public function remove($type)
	{
		if ( ! empty($_POST))
		{
			$method = 'remove' . ucfirst($type);

			if (method_exists($this, $method))
			{
				return $this->$method(ee()->input->post('id'));
			}
		}

		show_404();
	}

	/**
	 * Dispatch method for the various things that can be edit
	 */
	public function settings($type)
	{
		$parameters = array_slice(func_get_args(), 1);
		$method = 'settingsFor' . ucfirst($type);

		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $parameters);
		}

		show_404();
	}

	// --------------------------------------------------------------------

	private function createBoard()
	{
		$errors = NULL;

		$defaults = array(
			'board_id'						=> '',
			'board_label'					=> '',
			'board_name'					=> '',
			'board_enabled'					=> 'y',
			'board_forum_trigger'			=> 'forums',
			'board_site_id'					=> 1,
			'board_alias_id'				=> 0,
			'board_allow_php'				=> 'n',
			'board_php_stage'				=> 'o',
			'board_install_date'			=> 0,
			'board_forum_url'				=> ee()->functions->create_url('forums'),
			'board_default_theme'			=> 'default',
			'board_upload_path'				=> '',
			'board_topics_perpage'			=> 25,
			'board_posts_perpage'			=> 15,
			'board_topic_order'				=> 'r',
			'board_post_order'				=> 'a',
			'board_hot_topic'				=> 10,
			'board_max_post_chars'			=> 6000,
			'board_post_timelock'			=> 0,
			'board_display_edit_date'		=> 'n',
			'board_text_formatting'			=> 'xhtml',
			'board_html_formatting'			=> 'safe',
			'board_allow_img_urls'			=> 'n',
			'board_auto_link_urls'			=> 'y',
			'board_notify_emails'			=> '',
			'board_notify_emails_topics'	=> '',
			'board_max_attach_perpost'		=> 3,
			'board_max_attach_size'			=> 75,
			'board_max_width'				=> 800,
			'board_max_height'				=> 600,
			'board_attach_types'			=> 'img',
			'board_use_img_thumbs'			=> ($this->isGdAvailable()) ? 'y' : 'n',
			'board_thumb_width'				=> 100,
			'board_thumb_height'			=> 100,
			'board_forum_permissions'		=> $this->getDefaultForumPermissions(),
			'board_use_deft_permissions'	=> 'n',
			'board_recent_poster_id'		=> '0',
			'board_recent_poster'			=> '',
			'board_enable_rss'				=> 'y',
			'board_use_http_auth'			=> 'n',
		);

		$board = ee('Model')->make('forum:Board', $defaults);

		$result = $this->validateBoard($board);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveBoardAndRedirect($board);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('create_forum_board'),
			'base_url' => ee('CP/URL')->make($this->base . 'create/board'),
			'save_btn_text' => 'btn_save_board',
			'save_btn_text_working' => 'btn_saving',
			'tabs' => array(
				'board' => $this->getBoardForm($board, $errors),
				'forums' => $this->getBoardForumsForm($board, $errors),
				'permissions' => $this->getBoardPermissionsForm($board, $errors)
			),
			'sections' => array(),
			'required' => TRUE
		);

		ee()->cp->add_js_script('plugin', 'ee_url_title');
		ee()->javascript->output('
			$("input[name=board_label]").bind("keyup keydown", function() {
				$(this).ee_url_title("input[name=board_name]");
			});
		');

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar();

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base)->compile() => lang('forum_listing')
			),
			'heading'    => lang('create_forum_board'),
		);
	}

	private function editBoard($id)
	{
		$errors = NULL;

		$board = ee('Model')->get('forum:Board', $id)->first();
		if ( ! $board)
		{
			show_404();
		}

		$result = $this->validateBoard($board);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveBoardAndRedirect($board);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => sprintf(lang('edit_forum_board'), $board->board_label),
			'base_url' => ee('CP/URL')->make($this->base . 'edit/board/' . $id),
			'save_btn_text' => 'btn_save_board',
			'save_btn_text_working' => 'btn_saving',
			'tabs' => array(
				'board' => $this->getBoardForm($board, $errors),
				'forums' => $this->getBoardForumsForm($board, $errors),
				'permissions' => $this->getBoardPermissionsForm($board, $errors)
			),
			'sections' => array(),
			'required' => TRUE
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base . 'index/' . $id)->compile() => $board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function validateBoard($board)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$action = ($board->isNew()) ? 'create' : 'edit';

		$board->set($_POST);
		$result = $board->validate();

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_forum_board_error'))
				->addToBody(lang($action . '_forum_board_error_desc'))
				->now();
		}

		return $result;
	}

	private function saveBoardAndRedirect($board)
	{
		$action = ($board->isNew()) ? 'create' : 'edit';

		foreach ($_POST['permissions'] as $key => $value)
		{
			$board->setPermission($key, $value);
		}

		$board->save();

		$this->installSpecialtyTemplates($board->board_site_id);

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang($action . '_forum_board_success'))
			->addToBody(sprintf(lang($action . '_forum_board_success_desc'), $board->board_label))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . '/index/' . $board->board_id));
	}

	private function getBoardForm($board, $errors)
	{
		$html = '';

		$site = '';

		if (ee()->config->item('multiple_sites_enabled') == 'y')
		{
			$site = array(
				'title' => 'site',
				'fields' => array(
					'board_site_id' => array(
						'type' => 'select',
						'choices' => ee('Model')->get('Site')->all()->getDictionary('site_id', 'site_label'),
						'value' => $board->board_site_id,
					)
				)
			);
		}

		$sections = array(
			array(
				array(
					'title' => 'enable_board',
					'desc' => 'enable_board_desc',
					'fields' => array(
						'board_enabled' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $board->board_enabled,
						)
					)
				),
				array(
					'title' => 'name',
					'desc' => 'name_desc',
					'fields' => array(
						'board_label' => array(
							'type' => 'text',
							'value' => $board->board_label,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'alphadash_desc',
					'fields' => array(
						'board_name' => array(
							'type' => 'text',
							'value' => $board->board_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'forum_directory',
					'desc' => 'forum_directory_desc',
					'fields' => array(
						'board_forum_url' => array(
							'type' => 'text',
							'value' => $board->getRawProperty('board_forum_url'),
							'required' => TRUE
						)
					)
				),
				$site,
				array(
					'title' => 'forum_url_segment',
					'desc' => 'forum_url_segment_desc',
					'fields' => array(
						'board_forum_trigger' => array(
							'type' => 'text',
							'value' => $board->board_forum_trigger,
						)
					)
				),
				array(
					'title' => 'default_theme',
					'fields' => array(
						'board_default_theme' => array(
							'type' => 'select',
							'choices' => ee('ee:Theme')->listThemes('forum'),
							'value' => $board->board_default_theme,
						)
					)
				),
			),
			'php_parsing' => array(
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
					'title' => 'allow_php',
					'desc' => 'allow_php_desc',
					'caution' => TRUE,
					'fields' => array(
						'board_allow_php' => array(
							'type' => 'yes_no',
							'value' => $board->board_allow_php,
						)
					)
				),
				array(
					'title' => 'php_parsing_stage',
					'desc' => 'php_parsing_stage_desc',
					'fields' => array(
						'board_php_stage' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'i' => 'input',
								'o' => 'output'
							),
							'value' => $board->board_php_stage,
						)
					)
				),
			),
			'attachment_settings' => array(
				array(
					'title' => 'attachments_per_post',
					'desc' => 'attachments_per_post_desc',
					'fields' => array(
						'board_max_attach_perpost' => array(
							'type' => 'text',
							'value' => $board->board_max_attach_perpost,
						)
					)
				),
				array(
					'title' => 'upload_directory',
					'desc' => 'upload_directory_desc',
					'fields' => array(
						'board_upload_path' => array(
							'type' => 'text',
							'value' => $board->getRawProperty('board_upload_path'),
						)
					)
				),
				array(
					'title' => 'allowed_file_types',
					'fields' => array(
						'board_attach_types' => array(
							'type' => 'select',
							'choices' => array(
								'img' => lang('images_only'),
								'all' => lang('all_files')
							),
							'value' => $board->board_attach_types,
						)
					)
				),
				array(
					'title' => 'file_size',
					'desc' => 'file_size_desc',
					'fields' => array(
						'board_max_attach_size' => array(
							'type' => 'text',
							'value' => $board->board_max_attach_size,
						)
					)
				),
				array(
					'title' => 'image_width',
					'desc' => 'image_width_desc',
					'fields' => array(
						'board_max_width' => array(
							'type' => 'text',
							'value' => $board->board_max_width,
						)
					)
				),
				array(
					'title' => 'image_height',
					'desc' => 'image_height_desc',
					'fields' => array(
						'board_max_height' => array(
							'type' => 'text',
							'value' => $board->board_max_height,
						)
					)
				),
				array(
					'title' => 'enable_thumbnail_creation',
					'desc' => 'enable_thumbnail_creation_desc',
					'fields' => array(
						'board_use_img_thumbs' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $board->board_use_img_thumbs,
						)
					)
				),
				array(
					'title' => 'thumbnail_width',
					'desc' => 'thumbnail_width_desc',
					'fields' => array(
						'board_thumb_width' => array(
							'type' => 'text',
							'value' => $board->board_thumb_width,
						)
					)
				),
				array(
					'title' => 'thumbnail_height',
					'desc' => 'thumbnail_height_desc',
					'fields' => array(
						'board_thumb_height' => array(
							'type' => 'text',
							'value' => $board->board_thumb_height,
						)
					)
				),
			)
		);

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('ee:_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	private function getBoardForumsForm($board, $errors)
	{
		$html = '';

		ee()->load->model('addons_model');
		$fmt_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$sections = array(
			array(
				array(
					'title' => 'topics_per_page',
					'desc' => 'topics_per_page_desc',
					'fields' => array(
						'board_topics_perpage' => array(
							'type' => 'text',
							'value' => $board->board_topics_perpage,
						)
					)
				),
				array(
					'title' => 'posts_per_page',
					'desc' => 'posts_per_page_desc',
					'fields' => array(
						'board_posts_perpage' => array(
							'type' => 'text',
							'value' => $board->board_posts_perpage,
						)
					)
				),
				array(
					'title' => 'topic_ordering',
					'desc' => 'topic_ordering_desc',
					'fields' => array(
						'board_topic_order' => array(
							'type' => 'select',
							'choices' => array(
								'r' => lang('most_recent_post'),
								'a' => lang('most_recent_first'),
								'd' => lang('most_recent_last'),
							),
							'value' => $board->board_topic_order,
						)
					)
				),
				array(
					'title' => 'post_ordering',
					'desc' => 'post_ordering_desc',
					'fields' => array(
						'board_post_order' => array(
							'type' => 'select',
							'choices' => array(
								'a' => lang('most_recent_first'),
								'd' => lang('most_recent_last'),
							),
							'value' => $board->board_post_order,
						)
					)
				),
				array(
					'title' => 'hot_topics',
					'desc' => 'hot_topics_desc',
					'fields' => array(
						'board_hot_topic' => array(
							'type' => 'text',
							'value' => $board->board_hot_topic,
						)
					)
				),
				array(
					'title' => 'allowed_characters',
					'desc' => 'allowed_characters_desc',
					'fields' => array(
						'board_max_post_chars' => array(
							'type' => 'text',
							'value' => $board->board_max_post_chars,
						)
					)
				),
				array(
					'title' => 'posting_throttle',
					'desc' => 'posting_throttle_desc',
					'fields' => array(
						'board_post_timelock' => array(
							'type' => 'text',
							'value' => $board->board_post_timelock,
						)
					)
				),
				array(
					'title' => 'show_editing_dates',
					'desc' => 'show_editing_dates_desc',
					'fields' => array(
						'board_display_edit_date' => array(
							'type' => 'yes_no',
							'value' => $board->board_display_edit_date,
						)
					)
				),
			),
			'notification_settings' => array(
				array(
					'title' => 'topic_notifications',
					'desc' => 'topic_notifications_desc',
					'fields' => array(
						'board_enable_notify_emails_topics' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							// 'value' => $board->board_enable_notify_emails_topics,
						),
						'board_notify_emails_topics' => array(
							'type' => 'text',
							'value' => $board->board_notify_emails_topics,
							'attrs' => 'placeholder="' . lang('recipients'). '"'
						),
					)
				),
				array(
					'title' => 'reply_notification',
					'desc' => 'reply_notification_desc',
					'fields' => array(
						'board_enable_notify_emails' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							// 'value' => $board->board_enable_notify_emails,
						),
						'board_notify_emails' => array(
							'type' => 'text',
							'value' => $board->board_notify_emails,
							'attrs' => 'placeholder="' . lang('recipients'). '"'
						),
					)
				),
			),
			'text_and_html_formatting' => array(
				array(
					'title' => 'text_formatting',
					'desc' => 'text_formatting_desc',
					'fields' => array(
						'board_text_formatting' => array(
							'type' => 'select',
							'choices' => $fmt_options,
							'value' => $board->board_text_formatting,
						)
					)
				),
				array(
					'title' => 'html_formatting',
					'fields' => array(
						'board_html_formatting' => array(
							'type' => 'select',
							'choices' => array(
								'all'  => lang('html_all'),
								'safe' => lang('html_safe'),
								'none' => lang('html_none'),
							),
							'value' => $board->board_html_formatting,
						)
					)
				),
				array(
					'title' => 'autolink_urls',
					'desc' => 'autolink_urls_desc',
					'fields' => array(
						'board_auto_link_urls' => array(
							'type' => 'yes_no',
							'value' => $board->board_auto_link_urls,
						)
					)
				),
				array(
					'title' => 'allow_image_hotlinking',
					'desc' => 'allow_image_hotlinking_desc',
					'fields' => array(
						'board_allow_img_urls' => array(
							'type' => 'yes_no',
							'value' => $board->board_allow_img_urls,
						)
					)
				),
			),
			'rss_settings' => array(
				array(
					'title' => 'enable_rss',
					'desc' => 'enable_rss_desc',
					'fields' => array(
						'board_enable_rss' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $board->board_enable_rss,
						)
					)
				),
				array(
					'title' => 'enable_http_auth_for_rss',
					'desc' => 'enable_http_auth_for_rss_desc',
					'fields' => array(
						'board_use_http_auth' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $board->board_use_http_auth,
						)
					)
				),
			),
		);

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('ee:_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	private function getBoardPermissionsForm($board, $errors)
	{
		$html = '';

		$member_groups = ee('Model')->get('MemberGroup')
			->fields('group_id', 'group_title')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', '1')
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		$member_groups = array_map(function($group_name) {
			return htmlentities($group_name, ENT_QUOTES, 'UTF-8');
		}, $member_groups);

		$sections = array(
			array(
				ee('CP/Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('permissions_warning'))
					->cannotClose()
					->render(),
				array(
					'title' => 'enable_default_permissions',
					'desc' => 'enable_default_permissions_desc',
					'fields' => array(
						'board_use_deft_permissions' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $board->board_use_deft_permissions,
						)
					)
				),
				array(
					'title' => 'view_forums',
					'desc' => 'view_forums_desc',
					'fields' => array(
						'permissions[can_view_forum]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('can_view_forum'),
						)
					)
				),
				array(
					'title' => 'view_hidden_forums',
					'desc' => 'view_hidden_forums_desc',
					'fields' => array(
						'permissions[can_view_hidden]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('can_view_hidden'),
						)
					)
				),
				array(
					'title' => 'view_posts',
					'desc' => 'view_posts_desc',
					'fields' => array(
						'permissions[can_view_topics]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('can_view_topics'),
						)
					)
				),
				array(
					'title' => 'start_topics',
					'desc' => 'start_topics_desc',
					'fields' => array(
						'permissions[can_post_topics]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('can_post_topics'),
						)
					)
				),
				array(
					'title' => 'reply_to_topics',
					'desc' => 'reply_to_topics_desc',
					'fields' => array(
						'permissions[can_post_reply]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('can_post_reply'),
						)
					)
				),
				array(
					'title' => 'upload',
					'desc' => 'upload_desc',
					'fields' => array(
						'permissions[upload_files]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('upload_files'),
						)
					)
				),
				array(
					'title' => 'report',
					'desc' => 'report_desc',
					'fields' => array(
						'permissions[can_report]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('can_report'),
						)
					)
				),
				array(
					'title' => 'search',
					'desc' => 'search_desc',
					'fields' => array(
						'permissions[can_search]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $board->getPermission('can_search'),
						)
					)
				),
			)
		);

		foreach ($sections as $name => $settings)
		{
			$html .= ee('View')->make('ee:_shared/form/section')
				->render(array('name' => $name, 'settings' => $settings, 'errors' => $errors));
		}

		return $html;
	}

	private function removeBoard($id)
	{
		$board = ee('Model')->get('forum:Board', $id)->first();

		if ( ! $board)
		{
			show_404();
		}

		$name = $board->board_label;

		$board->delete();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('forum_board_removed'))
			->addToBody(sprintf(lang('forum_board_removed_desc'), $name))
			->defer();

		$return = ee('CP/URL')->make($this->base);

		if (ee()->input->get_post('return'))
		{
			$return = ee('CP/URL')->decodeUrl(ee()->input->get_post('return'));
		}

		ee()->functions->redirect($return);
	}

	// --------------------------------------------------------------------

	private function createAlias()
	{
		$errors = NULL;

		$defaults = array(
			'board_id'						=> '',
			'board_label'					=> '',
			'board_name'					=> '',
			'board_enabled'					=> 'y',
			'board_forum_trigger'			=> 'forums',
			'board_site_id'					=> 1,
			'board_alias_id'				=> 0,
			'board_forum_url'				=> ee()->functions->create_url('forums'),
			'board_default_theme'			=> 'default',
			'board_forum_permissions'		=> $this->getDefaultForumPermissions(),
		);

		$alias = ee('Model')->make('forum:Board', $defaults);

		$result = $this->validateAlias($alias);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveAliasAndRedirect($alias);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('create_forum_alias'),
			'base_url' => ee('CP/URL')->make($this->base . 'create/alias'),
			'save_btn_text' => 'btn_save_alias',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->getAliasForm($alias),
			'errors' => $errors,
			'required' => TRUE
		);

		ee()->cp->add_js_script('plugin', 'ee_url_title');
		ee()->javascript->output('
			$("input[name=board_label]").bind("keyup keydown", function() {
				$(this).ee_url_title("input[name=board_name]");
			});
		');

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar();

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base)->compile() => lang('forum_listing')
			),
			'heading'    => lang('create_forum_alias'),
		);
	}

	private function editAlias($id)
	{
		$errors = NULL;

		$alias = ee('Model')->get('forum:Board', $id)->first();
		if ( ! $alias)
		{
			show_404();
		}

		$result = $this->validateAlias($alias);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveAliasAndRedirect($alias);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => sprintf(lang('edit_forum_board'), $alias->board_label),
			'base_url' => ee('CP/URL')->make($this->base . 'edit/alias/' . $id),
			'save_btn_text' => 'btn_save_alias',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->getAliasForm($alias),
			'errors' => $errors,
			'required' => TRUE
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base . 'index/' . $id)->compile() => $alias->board_label . ' '. lang('forum_listing')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function validateAlias($alias)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$action = ($alias->isNew()) ? 'create' : 'edit';

		$alias->set($_POST);
		$result = $alias->validate();

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_forum_alias_error'))
				->addToBody(lang($action . '_forum_alias_error_desc'))
				->now();
		}

		return $result;
	}

	private function saveAliasAndRedirect($alias)
	{
		$action = ($alias->isNew()) ? 'create' : 'edit';

		$alias->save();

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang($action . '_forum_alias_success'))
			->addToBody(sprintf(lang($action . '_forum_alias_success_desc'), $alias->board_label))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . '/index/' . $alias->board_alias_id));
	}

	private function getAliasForm($alias)
	{
		$boards = ee('Model')->get('forum:Board')
			->fields('board_id', 'board_label')
			->filter('board_alias_id', 0)
			->all()
			->getDictionary('board_id', 'board_label');

		$sections = array(
			array(
				array(
					'title' => 'enable_alias',
					'desc' => 'enable_board_desc',
					'fields' => array(
						'board_enabled' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $alias->board_enabled,
						)
					)
				),
				array(
					'title' => 'name',
					'desc' => 'name_desc',
					'fields' => array(
						'board_label' => array(
							'type' => 'text',
							'value' => $alias->board_label,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'short_name',
					'desc' => 'alphadash_desc',
					'fields' => array(
						'board_name' => array(
							'type' => 'text',
							'value' => $alias->board_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'alias_url',
					'desc' => 'alias_url_desc',
					'fields' => array(
						'board_forum_url' => array(
							'type' => 'text',
							'value' => $alias->getRawProperty('board_forum_url'),
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'alias_url_segment',
					'desc' => 'alias_url_segment_desc',
					'fields' => array(
						'board_forum_trigger' => array(
							'type' => 'text',
							'value' => $alias->board_forum_trigger,
						)
					)
				),
				array(
					'title' => 'forum_board',
					'desc' => 'forum_board_desc',
					'fields' => array(
						'board_alias_id' => array(
							'type' => 'select',
							'choices' => $boards,
							'value' => $alias->board_alias_id,
						)
					)
				),
			)
		);

		return $sections;
	}

	private function removeAlias($id)
	{
		$alias = ee('Model')->get('forum:Board', $id)->first();

		if ( ! $alias)
		{
			show_404();
		}

		$name = $alias->board_label;

		$alias->delete();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('forum_alias_removed'))
			->addToBody(sprintf(lang('forum_alias_removed_desc'), $name))
			->defer();

		$return = ee('CP/URL')->make($this->base);

		if (ee()->input->get_post('return'))
		{
			$return = ee('CP/URL')->decodeUrl(ee()->input->get_post('return'));
		}

		ee()->functions->redirect($return);
	}

	// --------------------------------------------------------------------

	private function createCategory($board_id)
	{
		$errors = NULL;

		$board = ee('Model')->get('forum:Board', $board_id)->first();
		if ( ! $board)
		{
			show_404();
		}

		if ( ! empty($board->board_forum_permissions)
			&& $board->board_use_deft_permissions)
		{
			$default_permissions = $board->board_forum_permissions;
		}
		else
		{
			$default_permissions = $this->getDefaultForumPermissions();
		}

		$defaults = array(
			'board_id' => $board_id,
			'forum_is_cat' => TRUE,
			'forum_permissions' => $default_permissions,
			// These cannot be NULL in the DB....
			'forum_topics_perpage' => 25,
			'forum_posts_perpage' => 15,
			'forum_hot_topic' => 10,
			'forum_max_post_chars' => 6000,
		);

		$category = ee('Model')->make('forum:Forum', $defaults);

		$result = $this->validateCategory($category);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveCategoryAndRedirect($category);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('create_category'),
			'base_url' => ee('CP/URL')->make($this->base . 'create/category/' . $board_id),
			'save_btn_text' => 'btn_save_category',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->categoryForm($category),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($board_id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base . 'index/' . $board_id)->compile() => $board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => lang('create_category'),
		);
	}

	private function editCategory($id)
	{
		$errors = NULL;

		$category = ee('Model')->get('forum:Forum', $id)->with('Board')->first();
		if ( ! $category)
		{
			show_404();
		}

		$result = $this->validateBoard($category);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveCategoryAndRedirect($category);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('edit_category'),
			'base_url' => ee('CP/URL')->make($this->base . 'edit/category/' . $id),
			'save_btn_text' => 'btn_save_category',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->categoryForm($category),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($category->Board->board_id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base . 'index/' . $category->Board->board_id)->compile() => $category->Board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function validateCategory($category)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$action = ($category->isNew()) ? 'create' : 'edit';

		$category->set($_POST);
		$result = $category->validate();

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_category_error'))
				->addToBody(lang($action . '_category_error_desc'))
				->now();
		}

		return $result;
	}

	private function saveCategoryAndRedirect($category)
	{
		$action = ($category->isNew()) ? 'create' : 'edit';

		$category->save();

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang($action . '_category_success'))
			->addToBody(sprintf(lang($action . '_category_success_desc'), $category->forum_name))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . '/index/' . $category->board_id));
	}

	private function categoryForm($category)
	{
		$sections = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'name_desc',
					'fields' => array(
						'forum_name' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $category->forum_name,
						)
					)
				),
				array(
					'title' => 'description',
					'desc' => 'description_desc',
					'fields' => array(
						'forum_description' => array(
							'type' => 'textarea',
							'value' => $category->forum_description,
						)
					)
				),
				array(
					'title' => 'status',
					'desc' => 'status_desc',
					'fields' => array(
						'forum_status' => array(
							'type' => 'select',
							'choices' => array(
								'o' => lang('live'),
								'c' => lang('hidden'),
								'a' => lang('read_only'),
							),
							'value' => $category->forum_status,
						)
					)
				),
				array(
					'title' => 'topic_notifications',
					'desc' => 'topic_notifications_desc',
					'fields' => array(
						'forum_enable_notify_emails' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							// 'value' => $category->forum_enable_notify_emails,
						),
						'forum_notify_emails' => array(
							'type' => 'text',
							'value' => $category->forum_notify_emails,
							'attrs' => 'placeholder="' . lang('recipients'). '"'
						),
					)
				),
				array(
					'title' => 'reply_notification',
					'desc' => 'reply_notification_desc',
					'fields' => array(
						'forum_enable_notify_emails_topics' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							// 'value' => $category->forum_enable_notify_emails_topics,
						),
						'forum_notify_emails_topics' => array(
							'type' => 'text',
							'value' => $category->forum_notify_emails_topics,
							'attrs' => 'placeholder="' . lang('recipients'). '"'
						),
					)
				),
			)
		);

		return $sections;
	}

	private function settingsForCategory($id)
	{
		$errors = NULL;

		$category = ee('Model')->get('forum:Forum', $id)->with('Board')->first();
		if ( ! $category)
		{
			show_404();
		}

		$return = ee('CP/URL')->make($this->base . '/index/' . $category->board_id);

		if ( ! empty($_POST))
		{
			foreach ($_POST['permissions'] as $key => $value)
			{
				$category->setPermission($key, $value);
			}

			$category->save();

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_category_settings_success'))
				->addToBody(sprintf(lang('edit_category_settings_success_desc'), $category->forum_name))
				->defer();

			ee()->functions->redirect($return);
		}

		$vars = array(
			'errors' => $errors,
			'cp_page_title' => sprintf(lang('category_permissions'), $category->forum_name),
			'base_url' => ee('CP/URL')->make($this->base . 'settings/category/' . $id),
			'save_btn_text' => 'btn_save_permissions',
			'save_btn_text_working' => 'btn_saving',
		);

		$member_groups = ee('Model')->get('MemberGroup')
			->fields('group_id', 'group_title')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', '1')
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		$member_groups = array_map(function($group_name) {
			return htmlentities($group_name, ENT_QUOTES, 'UTF-8');
		}, $member_groups);

		$vars['sections'] = array(
			array(
				ee('CP/Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('permissions_warning'))
					->cannotClose()
					->render(),
				array(
					'title' => 'view_category',
					'desc' => 'view_category_desc',
					'fields' => array(
						'permissions[can_view_forum]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $category->getPermission('can_view_forum'),
						)
					)
				),
				array(
					'title' => 'view_hidden_category',
					'desc' => 'view_hidden_category_desc',
					'fields' => array(
						'permissions[can_view_hidden]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $category->getPermission('can_view_hidden'),
						)
					)
				),
			)
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($category->Board->board_id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				$return->compile() => $category->Board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	// --------------------------------------------------------------------

	private function createForum($cat_id)
	{
		$errors = NULL;

		$category = ee('Model')->get('forum:Forum', $cat_id)
			->with('Board')
			->first();

		if ( ! $category)
		{
			show_404();
		}

		$board = $category->Board;

		if ( ! empty($board->board_forum_permissions)
			&& $board->board_use_deft_permissions)
		{
			$default_permissions = $board->board_forum_permissions;
		}
		else
		{
			$default_permissions = $this->getDefaultForumPermissions();
		}

		$defaults = array(
			'board_id' => $board->board_id,
			'forum_parent' => $cat_id,
			'forum_is_cat' => FALSE,
			'forum_permissions' => $default_permissions,
			'forum_topics_perpage' => 25,
			'forum_posts_perpage' => 15,
			'forum_hot_topic' => 10,
			'forum_max_post_chars' => 6000,
		);

		$forum = ee('Model')->make('forum:Forum', $defaults);

		$result = $this->validateForum($forum);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveForumAndRedirect($forum);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('create_forum'),
			'base_url' => ee('CP/URL')->make($this->base . 'create/forum/' . $cat_id),
			'save_btn_text' => 'btn_save_forum',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->forumForm($forum),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($board->board_id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'index/' . $board->board_id)->compile() => $board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => lang('create_forum'),
		);
	}

	private function editForum($id)
	{
		$errors = NULL;

		$forum = ee('Model')->get('forum:Forum', $id)->with('Board')->first();
		if ( ! $forum)
		{
			show_404();
		}

		$result = $this->validateBoard($forum);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveForumAndRedirect($forum);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('edit_forum'),
			'base_url' => ee('CP/URL')->make($this->base . 'edit/forum/' . $id),
			'save_btn_text' => 'btn_save_forum',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->forumForm($forum),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($forum->Board->board_id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'index/' . $forum->Board->board_id)->compile() => $forum->Board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function validateForum($forum)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$action = ($forum->isNew()) ? 'create' : 'edit';

		$forum->set($_POST);
		$result = $forum->validate();

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_forum_error'))
				->addToBody(lang($action . '_forum_error_desc'))
				->now();
		}

		return $result;
	}

	private function saveForumAndRedirect($forum)
	{
		$action = ($forum->isNew()) ? 'create' : 'edit';

		$forum->save();

		if ($action == 'create')
		{
			ee()->session->set_flashdata('forum_id', $forum->forum_id);
		}

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang($action . '_forum_success'))
			->addToBody(sprintf(lang($action . '_forum_success_desc'), $forum->forum_name))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . '/index/' . $forum->board_id));
	}

	private function forumForm($forum)
	{
		ee()->load->model('addons_model');
		$fmt_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$sections = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'name_desc',
					'fields' => array(
						'forum_name' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $forum->forum_name,
						)
					)
				),
				array(
					'title' => 'description',
					'desc' => 'description_desc',
					'fields' => array(
						'forum_description' => array(
							'type' => 'textarea',
							'value' => $forum->forum_description,
						)
					)
				),
				array(
					'title' => 'status',
					'desc' => 'status_desc',
					'fields' => array(
						'forum_status' => array(
							'type' => 'select',
							'choices' => array(
								'o' => lang('live'),
								'c' => lang('hidden'),
								'a' => lang('read_only'),
							),
							'value' => $forum->forum_status,
						)
					)
				),
			),
			'topic_and_post_settings' => array(
				array(
					'title' => 'topics_per_page',
					'desc' => 'topics_per_page_desc',
					'fields' => array(
						'forum_topics_perpage' => array(
							'type' => 'text',
							'value' => $forum->forum_topics_perpage,
						)
					)
				),
				array(
					'title' => 'posts_per_page',
					'desc' => 'posts_per_page_desc',
					'fields' => array(
						'forum_posts_perpage' => array(
							'type' => 'text',
							'value' => $forum->forum_posts_perpage,
						)
					)
				),
				array(
					'title' => 'topic_ordering',
					'desc' => 'topic_ordering_desc',
					'fields' => array(
						'forum_topic_order' => array(
							'type' => 'select',
							'choices' => array(
								'r' => lang('most_recent_post'),
								'a' => lang('most_recent_first'),
								'd' => lang('most_recent_last'),
							),
							'value' => $forum->forum_topic_order,
						)
					)
				),
				array(
					'title' => 'post_ordering',
					'desc' => 'post_ordering_desc',
					'fields' => array(
						'forum_post_order' => array(
							'type' => 'select',
							'choices' => array(
								'a' => lang('most_recent_first'),
								'd' => lang('most_recent_last'),
							),
							'value' => $forum->forum_post_order,
						)
					)
				),
				array(
					'title' => 'hot_topics',
					'desc' => 'hot_topics_desc',
					'fields' => array(
						'forum_hot_topic' => array(
							'type' => 'text',
							'value' => $forum->forum_hot_topic,
						)
					)
				),
				array(
					'title' => 'allowed_characters',
					'desc' => 'allowed_characters_desc',
					'fields' => array(
						'forum_max_post_chars' => array(
							'type' => 'text',
							'value' => $forum->forum_max_post_chars,
						)
					)
				),
				array(
					'title' => 'posting_throttle',
					'desc' => 'posting_throttle_desc',
					'fields' => array(
						'forum_post_timelock' => array(
							'type' => 'text',
							'value' => $forum->forum_post_timelock,
						)
					)
				),
				array(
					'title' => 'show_editing_dates',
					'desc' => 'show_editing_dates_desc',
					'fields' => array(
						'forum_display_edit_date' => array(
							'type' => 'yes_no',
							'value' => $forum->forum_display_edit_date,
						)
					)
				),
			),
			'notification_settings' => array(
				array(
					'title' => 'topic_notifications',
					'desc' => 'topic_notifications_desc',
					'fields' => array(
						'forum_enable_notify_emails' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							// 'value' => $forum->forum_enable_notify_emails,
						),
						'forum_notify_emails' => array(
							'type' => 'text',
							'value' => $forum->forum_notify_emails,
							'attrs' => 'placeholder="' . lang('recipients'). '"'
						),
					)
				),
				array(
					'title' => 'reply_notification',
					'desc' => 'reply_notification_desc',
					'fields' => array(
						'forum_enable_notify_emails_topics' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							// 'value' => $forum->forum_enable_notify_emails_topics,
						),
						'forum_notify_emails_topics' => array(
							'type' => 'text',
							'value' => $forum->forum_notify_emails_topics,
							'attrs' => 'placeholder="' . lang('recipients'). '"'
						),
					)
				),
			),
			'text_and_html_formatting' => array(
				array(
					'title' => 'text_formatting',
					'desc' => 'text_formatting_desc',
					'fields' => array(
						'forum_text_formatting' => array(
							'type' => 'select',
							'choices' => $fmt_options,
							'value' => $forum->forum_text_formatting,
						)
					)
				),
				array(
					'title' => 'html_formatting',
					'fields' => array(
						'forum_html_formatting' => array(
							'type' => 'select',
							'choices' => array(
								'all'  => lang('html_all'),
								'safe' => lang('html_safe'),
								'none' => lang('html_none'),
							),
							'value' => $forum->forum_html_formatting,
						)
					)
				),
				array(
					'title' => 'autolink_urls',
					'desc' => 'autolink_urls_desc',
					'fields' => array(
						'forum_auto_link_urls' => array(
							'type' => 'yes_no',
							'value' => $forum->forum_auto_link_urls,
						)
					)
				),
				array(
					'title' => 'allow_image_hotlinking',
					'desc' => 'allow_image_hotlinking_desc',
					'fields' => array(
						'forum_allow_img_urls' => array(
							'type' => 'yes_no',
							'value' => $forum->forum_allow_img_urls,
						)
					)
				),
			),
			'rss_settings' => array(
				array(
					'title' => 'enable_rss',
					'desc' => 'enable_rss_desc',
					'fields' => array(
						'forum_enable_rss' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $forum->forum_enable_rss,
						)
					)
				),
				array(
					'title' => 'enable_http_auth_for_rss',
					'desc' => 'enable_http_auth_for_rss_desc',
					'fields' => array(
						'forum_use_http_auth' => array(
							'type' => 'inline_radio',
							'choices' => array(
								'y' => 'enable',
								'n' => 'disable'
							),
							'value' => $forum->forum_use_http_auth,
						)
					)
				),
			),
		);

		return $sections;
	}

	private function settingsForForum($id)
	{
		$errors = NULL;

		$forum = ee('Model')->get('forum:Forum', $id)->with('Board')->first();
		if ( ! $forum)
		{
			show_404();
		}

		$return = ee('CP/URL')->make($this->base . '/index/' . $forum->board_id);

		if ( ! empty($_POST))
		{
			foreach ($_POST['permissions'] as $key => $value)
			{
				$forum->setPermission($key, $value);
			}

			$forum->save();

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_forum_settings_success'))
				->addToBody(sprintf(lang('edit_forum_settings_success_desc'), $forum->forum_name))
				->defer();

			ee()->functions->redirect($return);
		}

		$vars = array(
			'errors' => $errors,
			'cp_page_title' => sprintf(lang('forum_permissions'), $forum->forum_name),
			'base_url' => ee('CP/URL')->make($this->base . 'settings/forum/' . $id),
			'save_btn_text' => 'btn_save_permissions',
			'save_btn_text_working' => 'btn_saving',
		);

		$member_groups = ee('Model')->get('MemberGroup')
			->fields('group_id', 'group_title')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', '1')
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		$member_groups = array_map(function($group_name) {
			return htmlentities($group_name, ENT_QUOTES, 'UTF-8');
		}, $member_groups);

		$vars['sections'] = array(
			array(
				ee('CP/Alert')->makeInline('permissions-warn')
					->asWarning()
					->addToBody(lang('permissions_warning'))
					->cannotClose()
					->render(),
				array(
					'title' => 'view_forum',
					'desc' => 'view_forum_desc',
					'fields' => array(
						'permissions[can_view_forum]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('can_view_forum'),
						)
					)
				),
				array(
					'title' => 'view_hidden_forum',
					'desc' => 'view_hidden_forum_desc',
					'fields' => array(
						'permissions[can_view_hidden]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('can_view_hidden'),
						)
					)
				),
				array(
					'title' => 'view_posts',
					'desc' => 'view_posts_desc',
					'fields' => array(
						'permissions[can_view_topics]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('can_view_topics'),
						)
					)
				),
				array(
					'title' => 'start_topics',
					'desc' => 'start_topics_desc',
					'fields' => array(
						'permissions[can_post_topics]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('can_post_topics'),
						)
					)
				),
				array(
					'title' => 'reply_to_topics',
					'desc' => 'reply_to_topics_desc',
					'fields' => array(
						'permissions[can_post_reply]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('can_post_reply'),
						)
					)
				),
				array(
					'title' => 'upload',
					'desc' => 'upload_desc',
					'fields' => array(
						'permissions[upload_files]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('upload_files'),
						)
					)
				),
				array(
					'title' => 'report',
					'desc' => 'report_desc',
					'fields' => array(
						'permissions[can_report]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('can_report'),
						)
					)
				),
				array(
					'title' => 'search',
					'desc' => 'search_desc',
					'fields' => array(
						'permissions[can_search]' => array(
							'type' => 'checkbox',
							'choices' => $member_groups,
							'value' => $forum->getPermission('can_search'),
						)
					)
				),
			)
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($forum->Board->board_id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				$return->compile() => $forum->Board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function removeForums($ids)
	{
		if ( ! is_array($ids))
		{
			$ids = array($ids);
		}

		$forums = ee('Model')->get('forum:Forum', $ids)->all();

		$forum_names = $forums->pluck('forum_name');

		$forums->delete();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('forums_removed'))
			->addToBody(lang('forums_removed_desc'))
			->addToBody($forum_names)
			->defer();

		$return = ee('CP/URL')->make($this->base);

		if (ee()->input->get_post('return'))
		{
			$return = ee('CP/URL')->decodeUrl(ee()->input->get_post('return'));
		}

		ee()->functions->redirect($return);
	}


	// --------------------------------------------------------------------

	public function ranks()
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->removeRanks(ee()->input->post('selection'));
		}

		$ranks = ee('Model')->get('forum:Rank')->all();

		$table = ee('CP/Table', array('autosort' => TRUE));
		$table->setColumns(
			array(
				'title',
				'posts',
				'stars',
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR,
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText('no_ranks', 'create_new_rank', ee('CP/URL')->make($this->base . 'create/rank'));

		$rank_id = ee()->session->flashdata('rank_id');

		$data = array();
		foreach ($ranks as $rank)
		{
			$edit_url = ee('CP/URL')->make($this->base . 'edit/rank/' . $rank->rank_id);

			$row = array(
				array(
					'content' => $rank->rank_title,
					'href' => $edit_url
				),
				$rank->rank_min_posts,
				$rank->rank_stars,
				array('toolbar_items' => array(
						'edit' => array(
							'href' => $edit_url,
							'title' => lang('edit'),
						),
					)
				),
				array(
					'name' => 'selection[]',
					'value' => $rank->rank_id,
					'data'	=> array(
						'confirm' => lang('rank') . ': <b>' . htmlentities($rank->rank_title, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();

			if ($rank_id && $rank->rank_id == $rank_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $row
			);
		}
		$table->setData($data);

		$base_url = ee('CP/URL')->make($this->base . 'ranks');

		$vars = array(
			'cp_page_title' => lang('member_ranks'),
			'cp_heading' => lang('member_ranks'),
		);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$vars['pagination'] = ee('CP/Pagination', count($ranks))
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($base_url);

		$body = ee('View')->make('forum:ranks')->render($vars);

		ee()->javascript->set_global('lang.remove_confirm', lang('rank') . ': <b>### ' . lang('ranks') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		$this->generateSidebar('ranks');

		return array(
			'body'    => $body,
			'heading' => lang('member_ranks'),
		);
	}

	private function createRank()
	{
		$errors = NULL;

		$rank = ee('Model')->make('forum:Rank');

		$result = $this->validateRank($rank);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveRankAndRedirect($rank);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('create_member_rank'),
			'base_url' => ee('CP/URL')->make($this->base . 'create/rank/'),
			'save_btn_text' => 'btn_save_rank',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->rankForm($rank),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar('ranks');

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'ranks')->compile() => lang('member_ranks')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function editRank($id)
	{
		$errors = NULL;

		$rank = ee('Model')->get('forum:Rank', $id)->first();
		if ( ! $rank)
		{
			show_404();
		}

		$result = $this->validateRank($rank);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveRankAndRedirect($rank);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('edit_member_rank'),
			'base_url' => ee('CP/URL')->make($this->base . 'edit/rank/' . $id),
			'save_btn_text' => 'btn_save_rank',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->rankForm($rank),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar('ranks');

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'ranks')->compile() => lang('member_ranks')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function rankForm($rank)
	{
		$sections = array(
			array(
				array(
					'title' => 'rank_title',
					'desc' => 'rank_title_desc',
					'fields' => array(
						'rank_title' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $rank->rank_title,
						)
					)
				),
				array(
					'title' => 'posts',
					'desc' => 'posts_desc',
					'fields' => array(
						'rank_min_posts' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $rank->rank_min_posts,
						)
					)
				),
				array(
					'title' => 'stars',
					'desc' => 'stars_desc',
					'fields' => array(
						'rank_stars' => array(
							'type' => 'text',
							'required' => TRUE,
							'value' => $rank->rank_stars,
						)
					)
				),
			),
		);

		return $sections;
	}

	private function validateRank($rank)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$action = ($rank->isNew()) ? 'create' : 'edit';

		$rank->set($_POST);
		$result = $rank->validate();

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang($action . '_rank_error'))
				->addToBody(lang($action . '_rank_error_desc'))
				->now();
		}

		return $result;
	}

	private function saveRankAndRedirect($rank)
	{
		$action = ($rank->isNew()) ? 'create' : 'edit';

		$rank->save();

		if ($action == 'create')
		{
			ee()->session->set_flashdata('rank_id', $rank->rank_id);
		}

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang($action . '_rank_success'))
			->addToBody(sprintf(lang($action . '_rank_success_desc'), $rank->rank_title))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . '/ranks'));
	}

	private function removeRanks($ids)
	{
		if ( ! is_array($ids))
		{
			$ids = array($ids);
		}

		$ranks = ee('Model')->get('forum:Rank', $ids)->all();

		$rank_titles = $ranks->pluck('rank_title');

		$ranks->delete();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('ranks_removed'))
			->addToBody(lang('ranks_removed_desc'))
			->addToBody($rank_titles)
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . 'ranks', ee()->cp->get_url_state()));
	}

	// --------------------------------------------------------------------

	public function admins($board_id)
	{
		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->removeAdmins(ee()->input->post('selection'));
		}

		$board = ee('Model')->get('forum:Board', $board_id)->first();
		if ( ! $board)
		{
			show_404();
		}

		if (ee()->input->post('bulk_action') == 'remove')
		{
			$this->removeAdmins(ee()->input->post('selection'));
		}

		$admins = ee('Model')->get('forum:Administrator')
			// ->with('Member')
			// ->with('MemberGroup')
			->filter('board_id', $board_id)
			->all();

		$table = ee('CP/Table', array('autosort' => TRUE));
		$table->setColumns(
			array(
				'name',
				'type',
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$new_url = ee('CP/URL')->make($this->base . 'create/admin/' . $board_id);
		$table->setNoResultsText(sprintf(lang('no_found'), lang('forum_admins')), 'create_new_admin', $new_url);

		$admin_id = ee()->session->flashdata('admin_id');

		$data = array();
		foreach ($admins as $admin)
		{
			$row = array(
				$admin->getAdminName(),
				$admin->getType(),
				array(
					'name' => 'selection[]',
					'value' => $admin->admin_id,
					'data'	=> array(
						'confirm' => lang('admin') . ': <b>' . htmlentities($admin->getAdminName(), ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();

			if ($admin_id && $admin->admin_id == $admin_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $row
			);
		}
		$table->setData($data);

		$base_url = ee('CP/URL')->make($this->base . 'admins/' . $board_id);

		$vars = array(
			'cp_page_title'   => lang('administrators'),
			'cp_heading'      => lang('administrators'),
			'cp_heading_desc' => lang('administrators_desc'),
			'new_url'         => $new_url
		);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$vars['pagination'] = ee('CP/Pagination', count($admins))
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($base_url);

		$body = ee('View')->make('forum:admins')->render($vars);

		ee()->javascript->set_global('lang.remove_confirm', lang('admin') . ': <b>### ' . lang('admins') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		$this->generateSidebar($board_id);

		return array(
			'body'    => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'index/' . $board_id)->compile() => $board->board_label . ' '. lang('forum_listing')
			),
			'heading' => lang('administrators'),
		);
	}

	public function createAdmin($board_id)
	{
		$board = ee('Model')->get('forum:Board', $board_id)->first();
		if ( ! $board)
		{
			show_404();
		}

		$errors = NULL;

		$admin = ee('Model')->make('forum:Administrator', array('board_id' => $board_id));

		$result = $this->validateAdmin($admin);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$admin->save();

				ee()->session->set_flashdata('admin_id', $admin->admin_id);

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('create_administrator_success'))
					->addToBody(sprintf(lang('create_administrator_success_desc'), $admin->getAdminName()))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make($this->base . 'admins/' . $board_id));
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => lang('create_administrator'),
			'base_url' => ee('CP/URL')->make($this->base . 'create/admin/' . $board_id),
			'save_btn_text' => 'btn_save_administrator',
			'save_btn_text_working' => 'btn_saving',
		);

		$member_groups = ee('Model')->get('MemberGroup')
			->fields('group_id', 'group_title')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', '1')
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		$member_groups = array_map(function($group_name) {
			return htmlentities($group_name, ENT_QUOTES, 'UTF-8');
		}, $member_groups);

		$vars['sections'] = array(
			array(
				array(
					'title' => 'administrator_type',
					'desc' => 'administrator_type_desc',
					'fields' => array(
						'administrator_type_group' => array(
							'type' => 'radio',
							'name' => 'administrator_type',
							'choices' => array(
								'group' => lang('admin_type_member_group'),
							),
							'value' => 'group',
						),
						'member_group' => array(
							'type' => 'select',
							'choices' => $member_groups,
							'value' => 5
						),
						'administrator_type_individual' => array(
							'type' => 'radio',
							'name' => 'administrator_type',
							'choices' => array(
								'individual' => lang('admin_type_individual')
							),
						),
						'individual' => array(
							'type' => 'text',
							'value' => ''
						)
					)
				),
			)
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar($board_id);

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'index/' . $board_id)->compile() => $board->board_label . ' '. lang('forum_listing')
			),
			'heading'    => $vars['cp_page_title'],
		);
	}

	private function validateAdmin($admin)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$validator = ee('Validation')->make(array(
			'administrator_type' => 'required|enum[group,individual]',
			'member_group'       => 'whenAdministratorTypeIs[group]|validMemberGroup',
			'individual'         => 'whenAdministratorTypeIs[individual]|validMember',
		));

		$data = $_POST;

		$validator->defineRule('whenAdministratorTypeIs', function($key, $value, $parameters, $rule) use ($data)
		{
		  return ($data['administrator_type'] == $parameters[0]) ? TRUE : $rule->skip();
		});

		$validator->defineRule('validMemberGroup', function($key, $value) use ($admin)
		{
			if (ee('Model')->get('MemberGroup', $value)->count() == 1)
			{
				$admin->admin_group_id = $value;
				return TRUE;
			}

			return 'invalid_member_group';
		});

		$validator->defineRule('validMember', function($key, $value) use ($admin)
		{
			$member = ee('Model')->get('Member')
				->fields('member_id')
				->filter('username', $value)
				->first();

			if ($member)
			{
				$admin->admin_member_id = $member->member_id;
				return TRUE;
			}

			return 'invalid_username';
		});

		$result = $validator->validate($_POST);

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_administrator_error'))
				->addToBody(lang('create_administrator_error_desc'))
				->now();
		}

		return $result;
	}

	private function removeAdmins($ids)
	{
		if ( ! is_array($ids))
		{
			$ids = array($ids);
		}

		$admins = ee('Model')->get('forum:Administrator', $ids)->all();

		$forum_names = $admins->map(function($admin) {
			return $admin->getAdminName();
		});

		$admins->delete();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('admins_removed'))
			->addToBody(lang('admins_removed_desc'))
			->addToBody($forum_names)
			->defer();

		$return = ee('CP/URL')->make($this->base);

		if (ee()->input->get_post('return'))
		{
			$return = ee('CP/URL')->decodeUrl(ee()->input->get_post('return'));
		}

		ee()->functions->redirect($return);
	}

	// --------------------------------------------------------------------

	public function moderators($id)
	{
		$board = ee('Model')->get('forum:Board', $id)
			->order('board_id', 'asc')
			->first();

		if ( ! $board)
		{
			show_404();
		}

		$categories = array();
		$forum_id = ee()->session->flashdata('forum_id');

		$boards_categories = ee('Model')->get('forum:Forum')
			->filter('board_id', $id)
			->filter('forum_is_cat', 'y')
			->order('forum_order', 'asc')
			->all();

		$base_url = ee('CP/URL')->make($this->base . 'moderators/' . $id);

		foreach ($boards_categories as $i => $category)
		{
			$table = ee('CP/Table', array('autosort' => TRUE));
			$table->setColumns(
				array(
					$category->forum_name,
					'moderators' => array(
						'encode' => FALSE
					),
					'manage' => array(
						'type'	=> Table::COL_TOOLBAR,
					)
				)
			);
			$table->setNoResultsText(sprintf(lang('no_found'), lang('forums')), 'create_new_forum', ee('CP/URL')->make($this->base . 'create/forum/' . $category->forum_id));

			$data = array();
			foreach ($category->Forums->sortBy('forum_order') as $forum)
			{
				$moderators = array();
				foreach ($forum->Moderators as $mod)
				{
					$moderators[] = array(
						'name' => $mod->getModeratorName(),
						'edit_url' => ee('CP/URL')->make($this->base . 'edit/moderator/' . $mod->mod_id),
						'confirm' => lang('moderator') . ': <b>' . $mod->getModeratorName() . '</b>',
						'id' => $mod->mod_id
					);
				}

				$row = array(
					$forum->forum_name,
					(empty($moderators)) ? '' : ee('View')->make('forum:mod-subtable')->render(array('moderators' => $moderators)),
					array('toolbar_items' => array(
						'add' => array(
							'href' => ee('CP/URL')->make($this->base . 'create/moderator/' . $forum->forum_id),
							'title' => lang('add_moderator')
						)
					))
				);

				$attrs = array();

				if ($forum_id && $forum->forum_id == $forum_id)
				{
					$attrs = array('class' => 'selected');
				}

				$data[] = array(
					'attrs'		=> $attrs,
					'columns'	=> $row
				);
			}
			$table->setData($data);
			$categories[] = $table->viewData($base_url);
		}

		$vars = array(
			'board' => $board,
			'categories' => $categories,
			'base_url' => $base_url,
			'remove_url' => ee('CP/URL')->make($this->base . 'remove/moderator'),
		);

		$body = ee('View')->make('forum:moderators')->render($vars);

		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/addons/forums/moderators',
			),
		));

		$this->generateSidebar($id);

		return array(
			'body'    => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'index/' . $id)->compile() => $board->board_label . ' '. lang('forum_listing')
			),
			'heading' => lang('moderators'),
		);
	}

	private function createModerator($forum_id)
	{
		$forum = ee('Model')->get('forum:Forum', $forum_id)->first();
		if ( ! $forum)
		{
			show_404();
		}

		$errors = NULL;

		$defaults = array(
			'mod_forum_id' => $forum_id,
			'board_id' => $forum->board_id,
			'mod_member_name' => '',
		);

		$moderator = ee('Model')->make('forum:Moderator', $defaults);

		$result = $this->validateModerator($moderator);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveModeratorAndRedirect($moderator);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => sprintf(lang('create_moderator_in'), $forum->forum_name),
			'base_url' => ee('CP/URL')->make($this->base . 'create/moderator/' . $forum_id),
			'save_btn_text' => 'btn_save_moderator',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->moderatorForm($moderator),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar();

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'index/' . $forum->board_id)->compile() => $forum->Board->board_label . ' '. lang('forum_listing'),
				ee('CP/URL')->make($this->base. 'moderators/' . $forum_id)->compile() => lang('moderators')
			),
			'heading'    => lang('create_moderator'),
		);
	}

	private function editModerator($id)
	{
		$moderator = ee('Model')->get('forum:Moderator', $id)->first();

		if ( ! $moderator)
		{
			show_404();
		}

		$errors = NULL;

		$forum = $moderator->Forum;
		$forum_id = $forum->forum_id;

		$result = $this->validateModerator($moderator);

		if ($result instanceOf ValidationResult)
		{
			$errors = $result;

			if ($result->isValid())
			{
				$this->saveModeratorAndRedirect($moderator);
			}
		}

		$vars = array(
			'ajax_validate' => TRUE,
			'errors' => $errors,
			'cp_page_title' => sprintf(lang('edit_moderator_in'), $forum->forum_name),
			'base_url' => ee('CP/URL')->make($this->base . 'edit/moderator/' . $id),
			'save_btn_text' => 'btn_save_moderator',
			'save_btn_text_working' => 'btn_saving',
			'sections' => $this->moderatorForm($moderator),
		);

		$body = ee('View')->make('ee:_shared/form_with_box')->render($vars);

		$this->generateSidebar();

		return array(
			'body'       => $body,
			'breadcrumb' => array(
				ee('CP/URL')->make($this->base. 'index/' . $forum->board_id)->compile() => $forum->Board->board_label . ' '. lang('forum_listing'),
				ee('CP/URL')->make($this->base. 'moderators/' . $forum_id)->compile() => lang('moderators')
			),
			'heading'    => lang('edit_moderator'),
		);
	}

	private function moderatorForm($moderator)
	{

		$permissions = array();
		$keys = array(
			'mod_can_edit',
			'mod_can_move',
			'mod_can_delete',
			'mod_can_split',
			'mod_can_merge',
			'mod_can_change_status',
			'mod_can_announce',
			'mod_can_view_ip',
		);

		foreach ($keys as $key)
		{
			if ($moderator->$key)
			{
				$permissions[] = $key;
			}
		}

		$member_groups = ee('Model')->get('MemberGroup')
			->fields('group_id', 'group_title')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('group_id', '!=', '1')
			->order('group_title', 'asc')
			->all()
			->getDictionary('group_id', 'group_title');

		$member_groups = array_map(function($group_name) {
			return htmlentities($group_name, ENT_QUOTES, 'UTF-8');
		}, $member_groups);

		$sections = array(
			array(
				array(
					'title' => 'moderator_type',
					'desc' => 'moderator_type_desc',
					'fields' => array(
						'moderator_type_group' => array(
							'type' => 'radio',
							'name' => 'moderator_type',
							'choices' => array(
								'group' => lang('moderator_type_member_group'),
							),
							'value' => ($moderator->getType()) ?: 'group',
						),
						'member_group' => array(
							'type' => 'select',
							'choices' => $member_groups,
							'value' => ($moderator->mod_group_id) ?: 5
						),
						'moderator_type_individual' => array(
							'type' => 'radio',
							'name' => 'moderator_type',
							'choices' => array(
								'individual' => lang('moderator_type_individual')
							),
							'value' => ($moderator->getType()) ?: 'group',
						),
						'individual' => array(
							'type' => 'text',
							'value' => ($moderator->getType() == 'individual') ? $moderator->Member->username : ''
						)
					)
				),
				array(
					'title' => 'permissions',
					'desc' => 'permissions_desc',
					'fields' => array(
						'permissions' => array(
							'type' => 'checkbox',
							'wrap' => TRUE,
							'choices' => array(
								'mod_can_edit'          => lang('mod_can_edit'),
								'mod_can_move'          => lang('mod_can_move'),
								'mod_can_split'         => lang('mod_can_split'),
								'mod_can_merge'         => lang('mod_can_merge'),
								'mod_can_delete'        => lang('mod_can_delete'),
								'mod_can_change_status' => lang('mod_can_change_status'),
								'mod_can_announce'      => lang('mod_can_announce'),
								'mod_can_view_ip'       => lang('mod_can_view_ip'),
							),
							'value' => $permissions
						)
					)
				)
			)
		);

		return $sections;
	}

	private function validateModerator($moderator)
	{
		if (empty($_POST))
		{
			return FALSE;
		}

		$keys = array(
			'mod_can_edit',
			'mod_can_move',
			'mod_can_delete',
			'mod_can_split',
			'mod_can_merge',
			'mod_can_change_status',
			'mod_can_announce',
			'mod_can_view_ip',
		);

		foreach ($keys as $key)
		{
			$moderator->$key = in_array($key, $_POST['permissions']);
		}

		$validator = ee('Validation')->make(array(
			'moderator_type' => 'required|enum[group,individual]',
			'member_group'       => 'whenModeratorTypeIs[group]|validMemberGroup',
			'individual'         => 'whenModeratorTypeIs[individual]|validMember',
		));

		$data = $_POST;

		$validator->defineRule('whenModeratorTypeIs', function($key, $value, $parameters, $rule) use ($data)
		{
		  return ($data['moderator_type'] == $parameters[0]) ? TRUE : $rule->skip();
		});

		$validator->defineRule('validMemberGroup', function($key, $value) use ($moderator)
		{
			if (ee('Model')->get('MemberGroup', $value)->count() == 1)
			{
				$moderator->mod_group_id = $value;
				return TRUE;
			}

			return 'invalid_member_group';
		});

		$validator->defineRule('validMember', function($key, $value) use ($moderator)
		{
			$member = ee('Model')->get('Member')
				->fields('member_id', 'screen_name', 'username')
				->filter('username', $value)
				->first();

			if ($member)
			{
				$moderator->mod_member_id = $member->member_id;
				$moderator->mod_member_name = $member->getMemberName();
				return TRUE;
			}

			return 'invalid_username';
		});

		$result = $validator->validate($_POST);

		if ($response = $this->ajaxValidation($result))
		{
			ee()->output->send_ajax_response($response);
		}

		if ($result->failed())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('create_moderator_error'))
				->addToBody(lang('create_moderator_error_desc'))
				->now();
		}

		return $result;
	}

	private function saveModeratorAndRedirect($moderator)
	{
		$action = ($moderator->isNew()) ? 'create' : 'edit';

		$moderator->save();

		if ($action == 'create')
		{
			ee()->session->set_flashdata('mod_id', $moderator->mod_id);
		}

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang($action . '_moderator_success'))
			->addToBody(sprintf(lang($action . '_moderator_success_desc'), $moderator->getModeratorName()))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . 'moderators/' . $moderator->board_id));
	}

	private function removeModerator($id)
	{
		$moderator = ee('Model')->get('forum:Moderator', $id)->first();

		$board_id = $moderator->board_id;

		if ( ! $moderator)
		{
			show_404();
		}

		$name = $moderator->getModeratorName();

		$moderator->delete();

		ee('CP/Alert')->makeInline('entries-form')
			->asSuccess()
			->withTitle(lang('moderator_removed'))
			->addToBody(sprintf(lang('moderator_removed_desc'), $name))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($this->base . 'moderators/' . $board_id));
	}

	// --------------------------------------------------------------------

	/**
	 * Conditionally adds forum specific specialty templates for a given site
	 * id.
	 *
	 * @param int $site_id The site id for the templates.
	 * @return void
	 */
	private function installSpecialtyTemplates($site_id)
	{
		$templates = ee('Model')->get('ee:SpecialtyTemplate')
			->filter('site_id', $site_id)
			->filter('template_name', 'forum_post_notification')
			->count();

		// Already installed; don't do it again.
		if ($templates > 0)
		{
			return;
		}

		require_once APPPATH.'language/'.ee()->config->item('deft_lang').'/email_data.php';

		$data = array(
			'site_id'			=> $site_id,
			'template_type'		=> 'email',
			'template_subtype'	=> 'forums',
			'edit_date'			=> ee()->localize->now,
		);

		$template_names = array(
			'admin_notify_forum_post',
			'forum_post_notification',
			'forum_moderation_notification',
			'forum_report_notification',
		);

		foreach ($template_names as $template_name)
		{
			$title = $template_name . '_title';

			$data['template_name'] = $template_name;
			$data['data_title'] = addslashes(trim($title()));
			$data['template_data'] = addslashes($template_name());

			$template = ee('Model')->make('ee:SpecialtyTemplate', $data)->save();
		}
	}

	/**
	 * Is GD installed?
	 *
	 * @return	bool TRUE if it is available; FALSE if not.
	 */
	private function isGdAvailable()
	{
		if (! extension_loaded('gd'))
		{
			if (! function_exists('dl') OR ! @dl('gd.so'))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Returns a default set of permissions for forums
	 *
	 * @return array A array of key/value pairs representing permissions
	 */
	private function getDefaultForumPermissions()
	{
		require_once PATH_ADDONS.'forum/upd.forum.php';

		$UPD = new Forum_upd();
		return $UPD->forum_set_base_permissions();
	}

}

// EOF
