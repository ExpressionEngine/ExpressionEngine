<?php

namespace EllisLab\ExpressionEngine\Controller\Publish;

use EllisLab\ExpressionEngine\Library\CP\Table;

use EllisLab\ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder;

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
 * ExpressionEngine CP Publish/Comments Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Comments extends AbstractPublishController {

	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group_any(
			'can_moderate_comments',
			'can_edit_own_comments',
			'can_delete_own_comments',
			'can_edit_all_comments',
			'can_delete_all_comments'
			))
		{
			show_error(lang('unauthorized_access'));
		}
	}

	/**
	 * Displays all available comments
	 *
	 * @return void
	 */
	public function index()
	{
		if (ee()->input->post('bulk_action'))
		{
			$this->performBulkActions();
			ee()->functions->redirect(ee('CP/URL')->make('publish/comments', ee()->cp->get_url_state()));
		}

		$vars = array(
			'can_delete' => ee()->cp->allowed_group('can_delete_all_comments') && ee()->cp->allowed_group('can_delete_own_comments'),
			'can_moderate' => ee()->cp->allowed_group('can_moderate_comments'),
		);

		$channel = NULL;
		$base_url = ee('CP/URL')->make('publish/comments');

		$comments = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'));

		$channel_filter = ee('CP/EntryListing', ee()->input->get_post('search'))->createChannelFilter();
		if ($channel_filter->value())
		{
			$comments->filter('channel_id', $channel_filter->value());
			$channel = ee('Model')->get('Channel', $channel_filter->value())->first();
		}

		$status_filter = $this->createStatusFilter();
		if ($status_filter->value())
		{
			$comments->filter('status', $status_filter->value());
		}

		ee()->view->search_value = htmlentities(ee()->input->get_post('search'), ENT_QUOTES, 'UTF-8');
		if ( ! empty(ee()->view->search_value))
		{
			$base_url->setQueryStringVariable('search', ee()->view->search_value);
			$comments->filter('comment', 'LIKE', '%' . ee()->view->search_value . '%');
		}

		$filters = ee('CP/Filter')
			->add($channel_filter)
			->add($status_filter)
			->add('Date');

		$filter_values = $filters->values();

		if ( ! empty($filter_values['filter_by_date']))
		{
			if (is_array($filter_values['filter_by_date']))
			{
				$comments->filter('comment_date', '>=', $filter_values['filter_by_date'][0]);
				$comments->filter('comment_date', '<', $filter_values['filter_by_date'][1]);
			}
			else
			{
				$comments->filter('comment_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
			}
		}

		$count = $comments->count();

		// Add this last to get the right $count
		$filters->add('Perpage', $count, 'all_entries');

		ee()->view->filters = $filters->render($base_url);

		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$comments->limit($filter_values['perpage'])
			->offset($offset);

		$table = $this->buildTableFromCommentQuery($comments);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$vars['pagination'] = ee('CP/Pagination', $count)
			->perPage($filter_values['perpage'])
			->currentPage($page)
			->render($base_url);

		ee()->javascript->set_global('lang.remove_confirm', lang('comment') . ': <b>### ' . lang('comments') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		if ($channel)
		{
			ee()->view->cp_breadcrumbs = array(
				ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $channel->channel_id))->compile() => sprintf(lang('all_channel_entries'), $channel->channel_title),
			);
		}
		else
		{
			ee()->view->cp_breadcrumbs = array(
				ee('CP/URL')->make('publish/edit')->compile() => sprintf(lang('all_channel_entries'), $channel),
			);
		}

		ee()->view->cp_page_title = lang('all_comments');

		// Set the page heading
		if ( ! empty(ee()->view->search_value))
		{
			ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, ee()->view->search_value);
		}
		else
		{
			ee()->view->cp_heading = lang('all_comments');
		}

		ee()->cp->render('publish/comments/index', $vars);
	}

	/**
	 * Dilsplays all comments for a given entry
	 *
	 * @param int $entry_id The ID# of the entry in question
	 * @return void
	 */
	public function entry($entry_id)
	{
		if (ee()->input->post('bulk_action'))
		{
			$this->performBulkActions();
			ee()->functions->redirect(ee('CP/URL')->make('publish/comments/entry/' . $entry_id, ee()->cp->get_url_state()));
		}

		$vars = array();
		$base_url = ee('CP/URL')->make('publish/comments/entry/' . $entry_id);

		$entry = ee('Model')->get('ChannelEntry', $entry_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $entry)
		{
			show_error(lang('no_entries_matching_that_criteria'));
		}

		$comments = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('entry_id', $entry_id);

		$status_filter = $this->createStatusFilter();
		if ($status_filter->value())
		{
			$comments->filter('status', $status_filter->value());
		}

		ee()->view->search_value = htmlentities(ee()->input->get_post('search'), ENT_QUOTES, 'UTF-8');
		if ( ! empty(ee()->view->search_value))
		{
			$base_url->setQueryStringVariable('search', ee()->view->search_value);
			$comments->filter('comment', 'LIKE', '%' . ee()->view->search_value . '%');
		}

		$filters = ee('CP/Filter')
			->add($status_filter)
			->add('Date');

		$filter_values = $filters->values();

		if ( ! empty($filter_values['filter_by_date']))
		{
			if (is_array($filter_values['filter_by_date']))
			{
				$comments->filter('comment_date', '>=', $filter_values['filter_by_date'][0]);
				$comments->filter('comment_date', '<', $filter_values['filter_by_date'][1]);
			}
			else
			{
				$comments->filter('comment_date', '>=', ee()->localize->now - $filter_values['filter_by_date']);
			}
		}

		$count = $comments->count();

		// Add this last to get the right $count
		$filters->add('Perpage', $count, 'all_entries');

		ee()->view->filters = $filters->render($base_url);

		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$comments->limit($filter_values['perpage'])
			->offset($offset);

		$table = $this->buildTableFromCommentQuery($comments);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = $vars['table']['base_url'];

		$vars['pagination'] = ee('CP/Pagination', $count)
			->perPage($filter_values['perpage'])
			->currentPage($page)
			->render($base_url);

		ee()->javascript->set_global('lang.remove_confirm', lang('comment') . ': <b>### ' . lang('comments') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array(
				'cp/confirm_remove',
			),
		));

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $entry->channel_id))->compile() => sprintf(lang('all_channel_entries'), $entry->getChannel()->channel_title),
		);

		ee()->view->cp_page_title = sprintf(lang('all_comments_for_entry'), htmlentities($entry->title, ENT_QUOTES, 'UTF-8'));

		// Set the page heading
		if ( ! empty(ee()->view->search_value))
		{
			ee()->view->cp_heading = sprintf(lang('search_results_heading'), $count, htmlentities(ee()->view->search_value));
		}
		else
		{
			ee()->view->cp_heading = sprintf(lang('all_comments_for_entry'), htmlentities($entry->title, ENT_QUOTES, 'UTF-8'));
		}

		$vars['can_delete'] = ee()->cp->allowed_group_any(
			'can_delete_own_comments',
			'can_delete_all_comments'
		);
		$vars['can_moderate'] = ee()->cp->allowed_group('can_moderate_comments');

		ee()->cp->render('publish/comments/index', $vars);
	}

	public function edit($comment_id)
	{
		// Cannot remove if you cannot edit
		if ( ! ee()->cp->allowed_group('can_edit_all_comments')
		  && ! ee()->cp->allowed_group('can_edit_own_comments'))
		{
			show_error(lang('unauthorized_access'));
		}

		$comment = ee('Model')->get('Comment', $comment_id)
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		if ( ! $comment)
		{
			show_error(lang('no_comments'));
		}

		// You get an edit button if you can edit all comments or you can
		// edit your own comments and this comment is one of yours
		if ( ! ee()->cp->allowed_group('can_edit_all_comments')
			&& $comment->author_id = ee()->session->userdata('member_id'))
		{
			show_error(lang('unauthorized_access'));
		}

		$author_information = ee('View')->make('publish/comments/partials/author_information')
			->render(array('comment' => $comment));

		$title = $comment->getEntry()->title;

		$live_look_template = $comment->getChannel()->getLiveLookTemplate();

		if ($live_look_template)
		{
			$view_url = ee()->functions->create_url($live_look_template->getPath() . '/' . $comment->getEntry()->entry_id);
			$title = '<a href="' . ee()->cp->masked_url($view_url) . '" rel="external">' . $title . '</a>';
		}

		$move_desc = sprintf(lang('move_comment_desc'),
			$title,
			$comment->getChannel()->channel_title
		);

		$vars = array(
			'ajax_validate' => TRUE,
			'base_url' => ee('CP/URL')->make('publish/comments/edit/' . $comment_id),
			'save_btn_text' => 'btn_edit_comment',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				array(
					array(
						'title' => 'author_information',
						'desc' => 'author_information_desc',
						'fields' => array(
							'author' => array(
								'type' => 'html',
								'content' => $author_information,

							)
						)
					),
					array(
						'title' => 'status',
						'desc' => 'comment_status_desc',
						'fields' => array(
							'status' => array(
								'type' => 'select',
								'choices' => array(
									'o' => lang('open'),
									'c' => lang('closed'),
									'p' => lang('pending'),
									's' => lang('spam')
								),
								'value' => $comment->status
							)
						)
					),
					array(
						'title' => 'comment_content',
						'desc' => 'comment_content_desc',
						'fields' => array(
							'comment' => array(
								'type' => 'textarea',
								'value' => $comment->comment,
								'required' => TRUE
							)
						)
					),
					array(
						'title' => 'move_comment',
						'desc' => $move_desc,
						'fields' => array(
							'move' => array(
								'type' => 'text'
							)
						)
					),
				)
			)
		);

		ee()->load->library('form_validation');
		ee()->form_validation->set_rules(array(
			array(
				'field' => 'comment',
				'label' => 'lang:comment',
				'rules' => 'required'
			),
			array(
				'field' => 'status',
				'label' => 'lang:status',
				'rules' => 'enum[o,c,p,s]'
			),
			array(
				'field' => 'move',
				'label' => 'lang:move',
				'rules' => 'is_natural'
			),
		));

		if (AJAX_REQUEST)
		{
			ee()->form_validation->run_ajax();
			exit;
		}
		elseif (ee()->form_validation->run() !== FALSE)
		{
			$comment->comment = ee()->input->post('comment');
			$comment->status = ee()->input->post('status');

			if (ee()->input->post('move'))
			{
				$comment->entry_id = ee()->input->post('move');
			}

			$comment->save();

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('edit_comment_success'))
				->addToBody(lang('edit_comment_success_desc'))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('publish/comments/edit/' . $comment_id));
		}
		elseif (ee()->form_validation->errors_exist())
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('edit_comment_error'))
				->addToBody(lang('edit_comment_error_desc'))
				->now();
		}

		ee()->view->cp_page_title = lang('edit_comment');

		ee()->view->cp_breadcrumbs = array(
			ee('CP/URL')->make('publish/comments')->compile() => lang('all_comments'),
		);

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * Builds a Table object from a Query of Comment model entitites
	 *
	 * @param Builder $comments A Query\Builder object for Comment model entities
	 * @return Table A Table instance
	 */
	private function buildTableFromCommentQuery(Builder $comments)
	{
		ee()->load->helper('text');
		$table = ee('CP/Table', array(
			'sort_dir' => 'desc',
			'sort_col' => 'column_comment_date',
		));

		$table->setColumns(
			array(
				'column_comment' => array(
					'encode' => FALSE
				),
				'column_comment_date',
				'column_ip_address',
				'column_status' => array(
					'type'	=> Table::COL_STATUS
				),
				'manage' => array(
					'type'	=> Table::COL_TOOLBAR
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(lang('no_comments'));

		$comments->order(str_replace('column_', '', $table->sort_col), $table->sort_dir);

		$data = array();

		$comment_id = ee()->session->flashdata('comment_id');

		foreach ($comments->all() as $comment)
		{
			switch ($comment->status)
			{
				case 'o':
					$status = lang('open');
					break;
				case 'c':
					$status = lang('closed');
					break;
				case 's':
					$status = lang('spam');
					break;
				default:
					$status = lang("pending");
			}

			$toolbar = array();

			// You get an edit button if you can edit all comments or you can
			// edit your own comments and this comment is one of yours
			if (ee()->cp->allowed_group('can_edit_all_comments')
				|| (ee()->cp->allowed_group('can_edit_own_comments')
					&& $comment->author_id = ee()->session->userdata('member_id')))
			{
				$toolbar = array(
					'edit' => array(
						'href' => ee('CP/URL')->make('publish/comments/edit/' . $comment->comment_id),
						'title' => lang('edit')
					)
				);
			}

			$column = array(
				ee('View')->make('publish/comments/partials/title')->render(array('comment' => $comment)),
				ee()->localize->human_time($comment->comment_date),
				$comment->ip_address,
				$status,
				array('toolbar_items' => $toolbar),
				array(
					'name' => 'selection[]',
					'value' => $comment->comment_id,
					'data' => array(
						'confirm' => lang('comment') . ': <b>' . htmlentities(ellipsize($comment->comment, 50), ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();

			if ($comment_id && $comment->comment_id == $comment_id)
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs'		=> $attrs,
				'columns'	=> $column
			);

		}
		$table->setData($data);
		return $table;
	}

	private function createStatusFilter()
	{
		$status = ee('CP/Filter')->make('filter_by_status', 'filter_by_status', array(
			'o' => lang('open'),
			'c' => lang('closed'),
			'p' => lang('pending'),
			's' => lang('spam')
		));
		$status->disableCustomValue();
		return $status;
	}

	private function performBulkActions()
	{
		switch(ee()->input->post('bulk_action'))
		{
			case 'remove':
				$this->remove(ee()->input->post('selection'));
				break;

			case 'open':
				$this->setStatus(ee()->input->post('selection'), 'o');
				break;

			case 'closed':
				$this->setStatus(ee()->input->post('selection'), 'c');
				break;

			case 'pending':
				$this->setStatus(ee()->input->post('selection'), 'p');
				break;
		}
	}

	private function remove($comment_ids)
	{
		// Cannot remove if you cannot edit
		if ( ! ee()->cp->allowed_group('can_delete_all_comments')
		  && ! ee()->cp->allowed_group('can_delete_own_comments'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! is_array($comment_ids))
		{
			$comment_ids = array($comment_ids);
		}

		$comments = ee('Model')->get('Comment', $comment_ids)
			->filter('site_id', ee()->config->item('site_id'));

		if ( ! ee()->cp->allowed_group('can_delete_all_comments')
		  && ee()->cp->allowed_group('can_delete_own_comments'))
		{
			$comments->filter('author_id', ee()->session->userdata('member_id'));
		}

		$comment_names = array();

		ee()->load->helper('text');

		foreach ($comments->all() as $comment)
		{
			$comment_names[] = ellipsize($comment->comment, 50);
		}

		$comments->delete();

		ee('CP/Alert')->makeInline('comments-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('comments_removed_desc'))
			->addToBody($comment_names)
			->defer();
	}

	private function setStatus($comment_ids, $status)
	{
		if ( ! ee()->cp->allowed_group('can_moderate_comments'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! is_array($comment_ids))
		{
			$comment_ids = array($comment_ids);
		}

		$comments = ee('Model')->get('Comment', $comment_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->set('status', $status)
			->update();

		$comments = ee('Model')->get('Comment', $comment_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$comment_names = array();

		ee()->load->helper('text');

		foreach ($comments as $comment)
		{
			$comment_names[] = ellipsize($comment->comment, 50);
		}

		switch ($status)
		{
			case 'o':
				$status = lang('open');
				break;
			case 'c':
				$status = lang('closed');
				break;
			default:
				$status = lang("pending");
		}

		ee('CP/Alert')->makeInline('comments-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(sprintf(lang('comments_status_updated_desc'), strtolower($status)))
			->addToBody($comment_names)
			->defer();
	}
}

// EOF
