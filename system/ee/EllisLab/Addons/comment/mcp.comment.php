<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Comment Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Comment_mcp {

	protected $comment_chars		= "20";
	protected $comment_leave_breaks = 'n';
	protected $base_url 			= '';
	protected $search_url;

	protected $_limit;
	protected $_offset;
	protected $_entry_id;
	protected $_keywords;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		if (REQ == 'CP')
		{
			$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment';

			if (ee()->cp->allowed_group('can_moderate_comments') &&
				ee()->cp->allowed_group('can_edit_all_comments') &&
				ee()->cp->allowed_group('can_delete_all_comments'))
			{
				ee()->cp->set_right_nav(array(
					'settings'	=> $this->base_url.AMP.'method=settings',
					'comments'	=> $this->base_url
				));
			}

			ee()->cp->add_js_script(array(
				'fp_module'	=> 'comment'
			));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Comments Home Page
	 *
	 * For the time being, this bad-boy is being simplified.  With some existing
	 * inefficiencies + datatables, the memory requirements to load the page
	 * with a large dataset (> 100k comments) was unacceptable.
	 *
	 * In an attempt to mitigate high memory usage, I'm purposely avoiding
	 * using a model and doing the queries right in this controller.  But Greg,
	 * that's "poor design!" When performance is a concern, I'm more than happy
	 * to drop using a model, since we aren't on an ORM.
	 */
	public function index()
	{
		$this->_permissions_check();

		ee()->load->library('table');
		ee()->load->helper('text');


		$columns = array(
			'_expand'		=> array(
				'header' => array('data' => '+/-', 'class' => 'expand'),
				'sort'	 => FALSE
			),
			'comment_edit_link' => array('header' => lang('comment')),
			'entry_title'	=> array('sort' => FALSE),
			'name'			=> array(),
			'email'			=> array(),
			'comment_date'	=> array('header' => lang('date')),
			'ip_address'	=> array(),
			'status'		=> array(),
			'_check'		=> array(
				'header' => form_checkbox('toggle_comments', 'true', FALSE, 'class="toggle_comments"'),
				'sort' => FALSE
			)
		);

		$filter_base_url = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment';

		if ($entry_id = ee()->input->get('entry_id'))
		{
			$filter_base_url .= AMP.'entry_id='.$entry_id;
		}

		ee()->table->set_base_url($filter_base_url);
		ee()->table->set_columns($columns);

		$params = array('perpage' => 50);
		$defaults = array('sort' => array('comment_date' => 'desc'));

		$data = ee()->table->datasource('_comment_data', $defaults, $params);

		ee()->javascript->set_global(array(
			'comment.run_script' => 'setup_index',
			'lang.selection_required' => lang('selection_required')
		));

		ee()->view->cp_page_title = lang('comments');

		$data = array_merge(array(
			'channel_select_opts' 	=> $this->_channel_select_opts(),
			'channel_selected'		=> $this->_channel,
			'status_select_opts'	=> $this->_status_select_opts(),
			'status_selected'		=> $this->_status,
			'date_select_opts'		=> $this->_date_select_opts(),
			'date_selected'			=> $this->_date_range,
			'keywords'				=> $this->_keywords,
			'form_options'			=> array(
				'close' 	=> lang('close_selected'),
				'open' 		=> lang('open_selected'),
				'pending' 	=> lang('pending_selected'),
				'null'		=> '------',
				'delete'	=> lang('delete_selected')
			)
		), $data);

		return ee()->load->view('index', $data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Comment Index Datasource
	 *
	 * @access public
	 */
	public function _comment_data($state, $params)
	{
		$this->_setup_query_filters($state, $params);

		list($total_count, $comment) = $this->_setup_index_query();

		$comments = array();

		if (count($comment))
		{
			$channel = $this->_get_channel_info($comment);
			$author = $this->_get_author_info($comment);
			$comments = $this->_merge_comment_data($comment, $channel, $author);

			$channel->free_result();
			$author->free_result();
		}

		$rows = array();

		while ($c = array_shift($comments))
		{
			$rows[] = (array) $c;
		}

		return array(
			'rows' => (array) $rows,
			'no_results' => lang('no_results'),
			'pagination' => array(
				'per_page' => $params['perpage'],
				'total_rows' => $total_count
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Date Select Options
	 *
	 * @return 	array
	 */
	 protected function _date_select_opts()
	 {
	 	return array(
	 		''	=> lang('date_range'),
	 		1 	=> lang('past_day'),
	 		7	=> lang('past_week'),
	 		31	=> lang('past_month'),
	 		182	=> lang('past_six_months'),
	 		365	=> lang('past_year')
		);
	 }

	// --------------------------------------------------------------------

	/**
	 * Status Select Options
	 *
	 * @return array
	 */
	protected function _status_select_opts()
	{
		return array(
			''		=> lang('filter_by_status'),
			'all'	=> lang('all'),
			'p'		=> lang('pending'),
			'o'		=> lang('open'),
			'c'		=> lang('closed')
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Channel filter select options
	 *
	 * @return array
	 */
	protected function _channel_select_opts()
	{
		// We only limit to channels they are assigned to if they can't
		// moderate and can't edit all
		if ( ! ee()->cp->allowed_group('can_moderate_comments') &&
			 ! ee()->cp->allowed_group('can_edit_all_comments'))
		{
			$query = ee()->channel_model->get_channels(
				(int) ee()->config->item('site_id'),
				array('channel_title', 'channel_id', 'cat_group')
			);
		}
		else
		{
			ee()->db->select('channel_title, channel_id, cat_group');
			ee()->db->where('site_id', (int) ee()->config->item('site_id'));
			ee()->db->order_by('channel_title');

			$query = ee()->db->get('channels');
		}

		$opts = array(
			''	=> lang('filter_by_channel')
		);

		if ( ! $query)
		{
			return array();
		}

		if ($query->num_rows() > 1)
		{
			$opts['all'] = lang('all');
		}

		foreach ($query->result() as $row)
		{
			$opts[$row->channel_id] = $row->channel_title;
		}

		return $opts;
	}

	// --------------------------------------------------------------------

	/**
	 * Merge Comment Data
	 *
	 * This is a...productive method.
	 *
	 * This method loops through the array of 50 comment db objects and
	 * adds in a few more vars that will be used in the view. Additionally,
	 * we alter some values such as status to make it human readable to get
	 * that logic out of the views where it has no bidness.
	 *
	 * @param 	array 	array of comment objects
	 * @param 	object 	db result from channel query
	 * @param 	object 	db result from authors query
	 * @return 	array 	array of altered comment objects
	 */
	protected function _merge_comment_data($comments, $channels, $authors)
	{
		ee()->load->library('typography');

		$config = array(
			'parse_images'	=> FALSE,
			'allow_headings'=> FALSE,
			'word_censor' 	=> (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE
		);

		ee()->typography->initialize($config);

		// There a result for authors here, or are they all anon?
		$authors = ( ! $authors->num_rows()) ? array() : $authors->result();

		foreach ($comments as &$comment)
		{
			// Drop the entry title into the comment object
			foreach ($channels->result() as $row)
			{
				if ($comment->entry_id == $row->entry_id)
				{
					$comment->entry_title = $row->title;
					break;
				}
			}

			// Get member info as well.
			foreach ($authors as $row)
			{
				if ($comment->author_id == $row->member_id)
				{
					$comment->author_screen_name = $row->screen_name;
					break;
				}
			}

			if ( ! isset($comment->author_screen_name))
			{
				$comment->author_screen_name = '';
			}

			// Convert stati to human readable form
			switch ($comment->status)
			{
				case 'o':
					$comment->status = lang('open');
					break;
				case 'c':
					$comment->status = lang('closed');
					break;
				default:
					$comment->status = lang("pending");
			}

			// Add the expand arrow
			$comment->_expand = array(
				'data' => '<img src="'.ee()->cp->cp_theme_url.'images/field_collapse.png" alt="'.lang('expand').'" />',
				'class' => 'expand'
			);

			// Add the toggle checkbox
			$comment->_check = form_checkbox(
				'toggle[]', $comment->comment_id, FALSE, 'class="comment_toggle"'
			);

			// Alter the email var
			$comment->email = mailto(
				$comment->email, '', 'class="less_important_link"'
			);

			$comment->comment_date = ee()->localize->human_time(
				$comment->comment_date
			);

			// Create comment_edit_link
			$comment->comment_edit_link = sprintf(
				"<a class=\"less_important_link\" href=\"%s\" title=\"%s\">%s</a>",
				$this->base_url.AMP.'method=edit_comment_form'.AMP.'comment_id='.$comment->comment_id,
				'edit',
				ellipsize($comment->comment, 50)
			);

			$comment->comment = array(
				'data' => '<div>'.ee()->typography->parse_type($comment->comment).'</div>',
				'colspan' => 7
			);

			$comment->details_link = array(
				'data' => anchor(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=edit_comment_form'.AMP.'comment_id='.$comment->comment_id, 'EDIT', 'class="submit"'),
				'colspan' => 2
			);
		}

		return $comments;
	}

	// --------------------------------------------------------------------

	/**
	 * Get comment author information
	 *
	 * @param 	array 	array of comment db objects
	 * @return 	object 	members db result object
	 */
	protected function _get_author_info($comments)
	{
		$ids = array();

		foreach ($comments as $comment)
		{
			if ($comment->author_id != 0) // db results are always string
			{
				$ids[] = (int) $comment->author_id;
			}
		}

		$ids = array_unique($ids);

		if (empty($ids))
		{
			$ids = array(0);
		}

		return ee()->db->select('member_id, screen_name, username')
			->where_in('member_id', $ids)
			->get('members');
	}

	// --------------------------------------------------------------------

	/**
	 * Get channel info.
	 *
	 * With large datasets/databases, a JOIN can be stupidly expensive,
	 * especially in a situation where the db server isn't properly tuned
	 * to make usage of system cache/buffers.  While I do appreciate the
	 * various distributions not making assumptions on what you want, sane
	 * defaults would be really nice.
	 *
	 * @param 	array 	array of comment db objects
	 * @return 	object 	channel_titles db result object
	 */
	protected function _get_channel_info($comments)
	{
		$ids = array();

		foreach ($comments as $comment)
		{
			$ids[] = (int) $comment->entry_id;
		}

		// Remove duplicate keys.
		$ids = array_unique($ids);

		if (empty($ids))
		{
			$ids = array(0);
		}

		return ee()->db->select('title, entry_id')
			->where_in('entry_id', $ids)
			->get('channel_titles');
	}

	// --------------------------------------------------------------------

	/**
	 * Setup query
	 *
	 * This method checks permissions on the logged in user to ensure they
	 * have been granted access to moderate/edit comments.  If they are,
	 * we give them everything, if not, we only give them the comments they
	 * authored.
	 *
	 * @return 	array 	$array($number_of_results, $comment_id_query);
	 */
	protected function _setup_index_query()
	{
		// get filters
		$this->_query_filters();

		foreach ($this->_sort as $col => $dir)
		{
			if ($col == 'comment_edit_link')
			{
				$col = 'comment';
			}

			$col = preg_replace('/[^\w-.]/', '', $col);

			ee()->db->order_by($col, $dir);
		}

		if ($this->_keywords)
		{
			ee()->db->where("(`exp_comments`.`name` LIKE '%".ee()->db->escape_like_str($this->_keywords)."%' OR `exp_comments`.`email` LIKE '%".ee()->db->escape_like_str($this->_keywords)."%' OR `exp_comments`.`comment` LIKE '%".ee()->db->escape_like_str($this->_keywords)."%')", NULL, TRUE);
		}

		$comment_q = ee()->db->get_where(
			'comments',
			array('site_id' => (int) ee()->config->item('site_id'))
		);

//		->get('comments', $this->_limit, $this->_offset);


		// This code will return every row in the selected channels if there is
		// no filter. Potentially hundreds of thousands of rows. That's no good.
		// We need the total rows, but a complicated search can be quite slow and
		// we don't want to double up on a slow query. So getting around it with
		// some private db methods for now. -pk

		$base_results = array();

		$count = $comment_q->num_rows();
		$perpage = $this->_limit;

		if ($this->_offset < $count)
		{
			$comment_q->_data_seek($this->_offset);

			while ($perpage && ($row = $comment_q->_fetch_object()))
			{
				$perpage--;
				$base_results[] = $row;
			}
		}

		$comment_q->free_result();

		return array($count, $base_results);
	}

	// --------------------------------------------------------------------

	protected function _query_filters()
	{
		// If the can ONLY edit their own comments- need to
		// bring in title table to limit on author
		if (( ! ee()->cp->allowed_group('can_moderate_comments') &&
			  ! ee()->cp->allowed_group('can_edit_all_comments')) &&
				ee()->cp->allowed_group('can_edit_own_comments'))
		{
			ee()->db->where('author_id', (int) ee()->session->userdata('member_id'));
		}

		if ($this->_channel)
		{
			ee()->db->where('channel_id', (int) $this->_channel);
		}

		if ($this->_status && $this->_status != 'all')
		{
			ee()->db->where('status', $this->_status);
		}

		if ($this->_date_range)
		{
			$date_range = time() - ($this->_date_range * 60 * 60 * 24);

			ee()->db->where('comment_date >', (int) $date_range);
		}

		if ($this->_entry_id)
		{
			ee()->db->where('entry_id', (int) $this->_entry_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Query Filters
	 *
	 * This method Sets up a few class properties based on query strings to
	 * filter the comments query on the index page.
	 *
	 * @return void
	 */
	protected function _setup_query_filters($state, $params)
	{
		$this->_entry_id = ee()->input->get('entry_id');
		$this->_channel = ee()->input->get_post('channel_id');
		$this->_status = ee()->input->get_post('status');
		$this->_date_range = ee()->input->get_post('date_range');
		$this->_keywords = ee()->input->get_post('keywords');

		if ($this->_channel == 'all')
		{
			$this->_channel = NULL;
		}

		$this->_sort = $state['sort'];
		$this->_offset = $state['offset'];

		$this->_limit = ($per_page = ee()->input->get('per_page')) ? $per_page : $params['perpage'];
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment Notification
	 *
	 * @return	string
	 */
	public function delete_comment_notification()
	{
		if ( ! $id = ee()->input->get_post('id') OR
			 ! $hash = ee()->input->get_post('hash'))
		{
			return FALSE;
		}

		if ( ! is_numeric($id))
		{
			return FALSE;
		}

		ee()->lang->loadfile('comment');

		ee()->load->library('subscription');
		ee()->subscription->init('comment', array('subscription_id' => $id), TRUE);
		ee()->subscription->unsubscribe('', $hash);

		$data = array(
			'title' 	=> lang('cmt_notification_removal'),
			'heading'	=> lang('thank_you'),
			'content'	=> lang('cmt_you_have_been_removed'),
			'redirect'	=> '',
			'link'		=> array(
				ee()->config->item('site_url'),
				stripslashes(ee()->config->item('site_name'))
			)
		);

		ee()->output->show_message($data);
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Comment Form
	 *
	 * @return	void
	 */
	public function edit_comment_form($comment_id = FALSE)
	{
		$this->_permissions_check();

		$can_edit = FALSE;

		ee()->load->library('table');

		$comment_id	= ( ! $comment_id) ? ee()->input->get_post('comment_id') : $comment_id;


		if ($comment_id == FALSE OR ! is_numeric($comment_id))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->helper('snippets');


		ee()->db->select('channel_titles.author_id as entry_author, title, channel_title, comment_require_email, comment, comment_id, comments.author_id, comments.status, name, email, url, location, comments.ip_address, comment_date, channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls');
		ee()->db->from(array('channel_titles', 'comments'));
		ee()->db->join('channels', 'exp_comments.channel_id = exp_channels.channel_id ', 'left');
		ee()->db->where('channel_titles.entry_id = '.ee()->db->dbprefix('comments.entry_id'));
		ee()->db->where('comments.comment_id', $comment_id);

		$query = ee()->db->get();

		if ($query->num_rows() === 0)
		{
			return FALSE;
		}

		if (ee()->cp->allowed_group('can_edit_all_comments'))
		{
			$can_edit = TRUE;
		}
		else
		{
			if ($query->row('entry_author') == ee()->session->userdata('member_id'))
			{
				$can_edit = TRUE;
			}
			else
			{
				if ( ! ee()->cp->allowed_group('can_moderate_comments'))
				{
					show_error(lang('unauthorized_access'));
				}
			}
		}

		$vars = $query->row_array();

		$vars['move_link'] = '';
		$vars['move_to'] = '';
		$vars['can_edit'] = $can_edit;

	 	$vars['status_select_options']['p'] = lang('pending');
		$vars['status_select_options']['o'] = lang('open');
		$vars['status_select_options']['c'] = lang('closed');

		$vars['status'] = (ee()->input->post('status')) ? ee()->input->post('status') : $vars['status'];

		// Instantiate Typography class
		$config = (ee()->config->item('comment_word_censoring') == 'y') ? array('word_censor' => TRUE) : array();

		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'	=> FALSE
		));

		$vars['display_comment'] = ee()->typography->parse_type(
			$vars['comment'],
			array(
				'text_format'	=> $vars['comment_text_formatting'],
				'html_format'	=> $vars['comment_html_formatting'],
				'auto_links'	=> $vars['comment_auto_link_urls'],
				'allow_img_url' => $vars['comment_allow_img_urls']
			)
		);

		$hidden = array(
			'comment_id'	=> $comment_id,
			'email'			=> $query->row('email')
		);

		ee()->javascript->set_global('comment.run_script', 'setup_edit');

		ee()->view->cp_page_title = lang('edit_comment');

		// a bit of a breadcrumb override is needed
		ee()->view->cp_breadcrumbs = array(
			$this->base_url => lang('comments')
		);

		$vars['hidden'] = $hidden;

		return ee()->load->view('edit', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * This permissions check is used in several places.
	 */
	private function _permissions_check()
	{
		if ( ! ee()->cp->allowed_group('can_moderate_comments')
		  && ! ee()->cp->allowed_group('can_edit_all_comments')
		  && ! ee()->cp->allowed_group('can_edit_own_comments'))
		{
			show_error(lang('unauthorized_access'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update Comment
	 *
	 * @return	void
	 */
	public function update_comment()
	{
		$this->_permissions_check();

		$comment_id = ee()->input->get_post('comment_id');

		if ($comment_id == FALSE OR ! is_numeric($comment_id))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->load->library('form_validation');
		$can_edit = FALSE;

		if (ee()->cp->allowed_group('can_edit_all_comments'))
		{
			$query = ee()->db->get_where('comments', array('comment_id' => $comment_id));
			$can_edit = TRUE;
		}
		else
		{
			ee()->db->select('channel_titles.author_id, comments.channel_id, comments.entry_id');
			ee()->db->from(array('channel_titles', 'comments'));
			ee()->db->where('channel_titles.entry_id = '.ee()->db->dbprefix('comments.entry_id'));
			ee()->db->where('comments.comment_id', $comment_id);

			$query = ee()->db->get();

			if ($query->row('author_id') != ee()->session->userdata('member_id'))
			{
				if ( ! ee()->cp->allowed_group('can_moderate_comments'))
				{
					show_error(lang('unauthorized_access'));
				}

				$can_edit = TRUE;
			}
		}

		if ($query->num_rows() == 0)
		{
			return false;
		}

		$row = $query->row_array();

   		$author_id = $row['author_id'];
		$channel_id = $row['channel_id'];
		$entry_id = $row['entry_id'];
		$current_status = $row['status'];

		$new_channel_id = $row['channel_id'];
		$new_entry_id = $row['entry_id'];

		//	 Are emails required?
		ee()->db->select('channels.comment_require_email');
		ee()->db->from(array('channels', 'comments'));
		ee()->db->where('comments.channel_id = '.ee()->db->dbprefix('channels.channel_id'));
		ee()->db->where('comments.comment_id', $comment_id);
		$query = ee()->db->get();

		if ($query->num_rows() == 0)
		{
			return show_error(lang('no_channel_exists'));
		}

		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		$status = ee()->input->post('status');

		//  If they can not edit- only the status may change
		if ( ! $can_edit)
		{
			if ( ! in_array($status, array('o', 'c', 'p')))
			{
				show_error(lang('unauthorized_access'));
			}

			$data = array('status' => $status);
			ee()->db->query(ee()->db->update_string('exp_comments', $data, "comment_id = '$comment_id'"));

			$this->update_stats(array($entry_id), array($channel_id), array($author_id));

			//  Did status change to open?  Notify
			if ($status == 'o' && $current_status != 'o')
			{
				$this->send_notification_emails(array($comment_id));
			}

			ee()->functions->clear_caching('all');

			$url = $this->base_url.AMP.'comment_id='.$comment_id;

			ee()->session->set_flashdata('message_success',
												lang('comment_updated'));
			ee()->functions->redirect($url);
		}

		// Error checks
		if ($author_id == 0)
		{
			// Fetch language file
			ee()->lang->loadfile('myaccount');

			if ($comment_require_email == 'y')
			{
				ee()->form_validation->set_rules('email', 'lang:email', 'callback__email_check');
			}
			else
			{
				ee()->form_validation->set_rules('email', 'lang:email', '');
			}

			ee()->form_validation->set_rules('name', 'lang:name', 'required');


			ee()->form_validation->set_rules('url', '', '');
			ee()->form_validation->set_rules('location', '', '');
		}


		// Are thy moving the comment?  Check for valid entry_id
		$move_to = ee()->input->get_post('move_to');
		$recount_ids = array();
		$recount_channels = array();

		if ($move_to != '')
		{
			$tcount = 0;

			if (ctype_digit($move_to))
			{
				ee()->db->select('title, entry_id, channel_id');
				ee()->db->where('entry_id', $move_to);
				$query = ee()->db->get('channel_titles');

				$tcount = $query->num_rows();
			}


			if ($tcount == 0)
			{
				ee()->form_validation->set_rules('move_to', 'lang:move_to', 'callback__move_check');
			}
			else
			{
				$row = $query->row();

				$new_entry_id = $row->entry_id;
				$new_channel_id = $row->channel_id;

				$recount_ids[] = $entry_id;
				$recount_channels[] = $channel_id;

				$recount_ids[] = $row->entry_id;
				$recount_channels[] = $row->channel_id;
			}
		}


		ee()->form_validation->set_rules('comment', 'lang:comment', 'required');

		ee()->form_validation->set_error_delimiters('<br /><span class="notice">', '<br />');

		if (ee()->form_validation->run() === FALSE)
		{
			return $this->edit_comment_form($comment_id);
		}

		// Build query

		if ($author_id == 0)
		{
			$data = array(
				'entry_id' => $new_entry_id,
				'channel_id' => $new_channel_id,
				'name'		=> ee()->input->post('name'),
				'email'		=> ee()->input->post('email'),
				'url'		=> ee()->input->post('url'),
				'location'	=> ee()->input->post('location'),
				'comment'	=> ee()->input->post('comment'),
				'status'	=> $status
			 );
		}
		else
		{
			$data = array(
				'entry_id' => $new_entry_id,
				'channel_id' => $new_channel_id,
				'comment'	=> ee()->input->post('comment'),
				'status'	=> $status
			 );
		}

		$data['edit_date'] = ee()->localize->now;

		ee()->db->query(ee()->db->update_string('exp_comments', $data, "comment_id = '$comment_id'"));

		if ($status != $current_status)
		{
			$this->update_stats(array($entry_id), array($channel_id), array($author_id));

			//  Did status change to open?  Notify
			if ($status == 'o' && $current_status != 'o')
			{
				$this->send_notification_emails(array($comment_id));
			}
		}


		if (count($recount_ids) > 0)
		{
			ee()->load->model('comment_model');

			ee()->comment_model->recount_entry_comments($recount_ids);

			// Quicker and updates just the channels
			foreach(array_unique($recount_channels) as $channel_id)
			{
				ee()->stats->update_comment_stats($channel_id, '', FALSE);
			}

			// Updates the total stats
			ee()->stats->update_comment_stats();
		}


		// -------------------------------------------
		// 'update_comment_additional' hook.
		//  - Add additional processing on comment update.
		//
			ee()->extensions->call('update_comment_additional', $comment_id, $data);
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		ee()->functions->clear_caching('all');

		$url = $this->base_url.AMP.'comment_id='.$comment_id;

		ee()->session->set_flashdata('message_success',  lang('comment_updated'));
		ee()->functions->redirect($url);
	}

	// --------------------------------------------------------------------

	/**
	 * Email Check
	 *
	 * callback function for form_validation, so it needs to be publicly
	 * accessible.
	 */
	public function _email_check($str)
	{
		// Is email missing?
		if ($str == '')
		{
			ee()->form_validation->set_message(
				'_email_check',
				lang('missing_email')
			);

			return FALSE;
		}

		// Is email valid?
		ee()->load->helper('email');

		if ( ! valid_email($str))
		{
			ee()->form_validation->set_message(
				'_email_check',
				lang('invalid_email_address')
			);

			return FALSE;
		}

		// Is email banned?
		if (ee()->session->ban_check('email', $str))
		{
			ee()->form_validation->set_message(
				'_email_check',
				lang('banned_email')
			);

			return FALSE;
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Move check -- form_validation callback
	 *
	 */
	public function _move_check($str)
	{
		// failed by definition
		ee()->form_validation->set_message(
			'_move_check',
			lang('invalid_entry_id')
		);

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Modify Comments
	 *
	 * @return	void
	 */
	public function modify_comments()
	{
		// This only happens if they submit with no comments checked, so we send
		// them home.
		if ( ! ee()->input->post('toggle') &&
			 ! ee()->input->get_post('comment_id'))
		{
			ee()->session->set_flashdata('message_failure',
											lang('no_valid_selections'));
			ee()->functions->redirect($this->base_url);
		}

		switch(ee()->input->post('action'))
		{
			case 'open':
				$this->change_comment_status('o');
			break;
			case 'close':
				$this->change_comment_status('c');
			break;
			case 'pending':
				$this->change_comment_status('p');
			break;
			default:
				return $this->delete_comment_confirm();
			break;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comments Confirmation
	 *
	 * @return	void
	 */
	public function delete_comment_confirm()
	{
		if ( ! ee()->cp->allowed_group('can_delete_all_comments')
		  && ! ee()->cp->allowed_group('can_delete_own_comments'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->cp->get_installed_modules();

		$blacklist_installed =  (isset(ee()->cp->installed_modules['blacklist'])) ? TRUE : FALSE;

		if ( ! ee()->input->post('toggle') && ! ee()->input->get_post('comment_id'))
		{
			ee()->session->set_flashdata('message_failure', lang('no_valid_selections'));
			ee()->functions->redirect($this->base_url);
		}

		ee()->load->library('table');
		$comments = array();

		if (ee()->input->post('toggle'))
		{
			foreach ($_POST['toggle'] as $key => $val)
			{
				$comments[] = $val;
			}
		}

		if (ee()->input->get_post('comment_id') !== FALSE && is_numeric(ee()->input->get_post('comment_id')))
		{
			$comments[] = ee()->input->get_post('comment_id');
		}

		if (count($comments) == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->db->select('channel_titles.author_id, title, comments.comment_id, comment, comments.ip_address');
		ee()->db->from(array('channel_titles', 'comments'));
		ee()->db->where('channel_titles.entry_id = '.ee()->db->dbprefix('comments.entry_id'));
		ee()->db->where_in('comments.comment_id', $comments);

		$comments	= array();

		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				if ( ! ee()->cp->allowed_group('can_delete_all_comments')  && ($row['author_id'] != ee()->session->userdata('member_id')))
				{
					continue;
				}

				$row['comment'] = strip_tags(str_replace(array("\t","\n","\r"), ' ', $row['comment']));
				$row['comment'] = ee()->functions->char_limiter(trim($row['comment']), 100);


				$comments[$row['comment_id']]['entry_title'] = $row['title'];
				$comments[$row['comment_id']]['comment'] = $row['comment'];
				$comments[$row['comment_id']]['ip_address'] = $row['ip_address'];
			}
		}

		if (count($comments) == 0)
		{
			ee()->session->set_flashdata('message_failure',
											lang('no_valid_selections'));
			ee()->functions->redirect($this->base_url);
		}

		ee()->view->cp_page_title = lang('delete_confirm');

		ee()->view->cp_breadcrumbs = array(
			$this->base_url => lang('comments')
		);

		$vars = array();

		$vars['hidden'] = array(
			'comment_ids'	=> implode('|', array_keys($comments))
		);

		$vars['blacklist_installed'] = (isset(ee()->cp->installed_modules['blacklist'])) ? TRUE : FALSE;

		$message = (count($comments) > 1) ? 'delete_comments_confirm' : 'delete_comment_confirm';

		$vars['comments'] = $comments;
		$vars['message'] = $message;

		return ee()->load->view('delete_comments', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Change Comment Status
	 *
	 * @param	string	new status
	 * @return	void
	 */
	public function change_comment_status($status = '')
	{
		$this->_permissions_check();

		$comments	= array();

		if (isset($_POST['toggle']) && is_array($_POST['toggle']))
		{
			foreach ($_POST['toggle'] as $key => $val)
			{
				$comments[$val] = $val;
			}
		}

		if(ee()->input->get_post('comment_id') !== FALSE && is_numeric(ee()->input->get_post('comment_id')))
		{
			$comments[ee()->input->get_post('comment_id')] = ee()->input->get_post('comment_id');
		}

		if (count($comments) == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$status = ($status == '') ? ee()->input->get('status') : $status;

		if ( ! in_array($status, array('o', 'c', 'p')))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->db->select('exp_comments.entry_id, exp_comments.channel_id, exp_comments.author_id, comment_id, exp_channel_titles.author_id AS entry_author');
		ee()->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
		ee()->db->where_in('comment_id', $comments);
		$query = ee()->db->get('comments');

		// Retrieve Our Results

		if ($query->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$entry_ids	= array();
		$author_ids = array();
		$channel_ids = array();


		foreach($query->result_array() as $row)
		{
			if (( ! ee()->cp->allowed_group('can_moderate_comments')
			   && ! ee()->cp->allowed_group('can_edit_all_comments'))
			   && ($row['entry_author'] != ee()->session->userdata('member_id')))
			{
				unset($comments[$row['comment_id']]);
				continue;
			}

			$entry_ids[]  = $row['entry_id'];
			$author_ids[] = $row['author_id'];
			$channel_ids[] = $row['channel_id'];
		}

		if (count($comments) == 0)
		{
			show_error(lang('unauthorized_access'));
		}


		$entry_ids	= array_unique($entry_ids);
		$author_ids = array_unique($author_ids);
		$channel_ids = array_unique($channel_ids);

		/** -------------------------------
		/**	 Change Status
		/** -------------------------------*/

		ee()->db->set('status', $status);
		ee()->db->where_in('comment_id', $comments);
		ee()->db->update('comments');

		$this->update_stats($entry_ids, $channel_ids, $author_ids);

		//	 Send email notification or remove notifications

		if ($status == 'o')
		{
			$this->send_notification_emails($comments);
		}

		if (ee()->extensions->active_hook('update_comment_additional'))
		{

			$qry = ee()->db->where_in('comment_id', $comments)
								->get('comments');

			foreach ($qry->result_array() as $row)
			{
				// -------------------------------------------
				// 'update_comment_additional' hook.
				//  - Add additional processing on comment update.
				//
					ee()->extensions->call('update_comment_additional', $row['comment_id'], $row);
					if (ee()->extensions->end_script === TRUE) return;
				//
				// -------------------------------------------
			}
		}

		ee()->functions->clear_caching('all');

		$url = $this->base_url;

		ee()->session->set_flashdata('message_success', lang('status_changed'));
		ee()->functions->redirect($url);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment
	 *
	 * @return	void
	 */
	public function delete_comment()
	{
		if ( ! ee()->cp->allowed_group('can_delete_all_comments') &&
			 ! ee()->cp->allowed_group('can_delete_own_comments'))
		{
			show_error(lang('unauthorized_access'));
		}

		$comment_id = ee()->input->post('comment_ids');

		if ($comment_id == FALSE)
		{
			show_error(lang('unauthorized_access'));
		}


		if ( ! preg_match("/^[0-9]+$/", str_replace('|', '', $comment_id)))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->db->where_in('comment_id', explode('|', $comment_id));
		$count = ee()->db->count_all_results('comments');

		if ($count == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->cp->get_installed_modules();

		$blacklist_installed =  (isset(ee()->cp->installed_modules['blacklist'])) ? TRUE : FALSE;

		ee()->db->select('channel_titles.author_id, channel_titles.entry_id, channel_titles.channel_id, channel_titles.comment_total, comments.ip_address');
		ee()->db->from(array('channel_titles', 'comments'));
		ee()->db->where('channel_titles.entry_id = '.ee()->db->dbprefix('comments.entry_id'));
		ee()->db->where_in('comments.comment_id', explode('|', $comment_id));

		$query = ee()->db->get();

		if ($query->num_rows() == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$entry_ids	= array();
		$author_ids = array();
		$channel_ids = array();
		$bad_ips = array();

		foreach($query->result_array() as $row)
		{
			$entry_ids[]  = $row['entry_id'];
			$author_ids[] = $row['author_id'];
			$channel_ids[] = $row['channel_id'];
			$bad_ips[] = $row['ip_address'];
		}

		$entry_ids	= array_unique($entry_ids);
		$author_ids = array_unique($author_ids);
		$channel_ids = array_unique($channel_ids);
		$ips['ip'] = array_unique($bad_ips);
		unset($bad_ips);


		if ( ! ee()->cp->allowed_group('can_delete_all_comments'))
		{
			foreach($query->result_array() as $row)
			{
				if ($row['author_id'] != ee()->session->userdata('member_id'))
				{
					show_error(lang('unauthorized_access'));
				}
			}
		}

		// If blacklist was checked- blacklist!
		if ($blacklist_installed && ee()->input->post('add_to_blacklist') == 'y')
		{
			include_once PATH_ADDONS.'blacklist/mcp.blacklist.php';

			$bl = new Blacklist_mcp();

			// Write to htaccess?
			$write_htacces = (ee()->session->userdata('group_id') == '1' && ee()->config->item('htaccess_path') != '')	? TRUE : FALSE;

			$blacklisted = $bl->update_blacklist($ips, $write_htacces, 'bool');
		}


		$comment_ids = explode('|', $comment_id);

		// -------------------------------------------
		// 'delete_comment_additional' hook.
		//  - Add additional processing on comment delete
		//
			ee()->extensions->call('delete_comment_additional', $comment_ids);
			if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		ee()->db->where_in('comment_id', $comment_ids);
		ee()->db->delete('comments');

		$this->update_stats($entry_ids, $channel_ids, $author_ids);

		ee()->functions->clear_caching('all');
		ee()->session->set_flashdata(
			'message_success',
			lang('comment_deleted')
		);

		ee()->functions->redirect($this->base_url);
	}

	// --------------------------------------------------------------------

	/**
	 * Send Notification Emails
	 *
	 * @return	void
	 */
	public function send_notification_emails($comments)
	{
		// Load subscription class
		ee()->load->library('subscription');

		// Instantiate Typography class
		ee()->load->library('typography');
		ee()->typography->initialize(array(
			'parse_images'		=> FALSE,
			'word_censor'		=> (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE
		));


		// Grab the required comments
		ee()->db->select('comment, comment_id, author_id, name, email, comment_date, entry_id');
		ee()->db->where_in('comment_id', $comments);
		$query = ee()->db->get('comments');


		// Sort based on entry
		$entries = array();

		foreach ($query->result() as $row)
		{
			if ( ! isset($entries[$row->entry_id]))
			{
				$entries[$row->entry_id] = array();
			}

			$entries[$row->entry_id][] = $row;
		}


		// Go through the entries and send subscriptions

		foreach ($entries as $entry_id => $comments)
		{
			ee()->subscription->init('comment', array('entry_id' => $entry_id), TRUE);

			// Grab them all
			$subscriptions = ee()->subscription->get_subscriptions();

			ee()->load->model('comment_model');
			$recipients = ee()->comment_model->fetch_email_recipients($entry_id, $subscriptions);

			if (count($recipients))
			{
				// Grab generic entry info

				$action_id	= ee()->functions->fetch_action_id('Comment_mcp', 'delete_comment_notification');

				ee()->db->select('channel_titles.site_id, channel_titles.title, channel_titles.entry_id, channel_titles.url_title, channels.channel_title, channels.comment_url, channels.channel_url, channels.channel_id');
				ee()->db->join('channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
				ee()->db->where('channel_titles.entry_id', $entry_id);
				$results = ee()->db->get('channel_titles');

				$overrides = ee()->config->get_cached_site_prefs($results->row('site_id'));
				$channel_url = parse_config_variables($results->row('channel_url'), $overrides);
				$comment_url = parse_config_variables($results->row('comment_url'), $overrides);

				$com_url = ($comment_url  == '') ? $channel_url : $comment_url;


				// Create an array of comments to add to the email

				$comments_swap = array();

				foreach ($comments as $c)
				{
					$comment_text = ee()->typography->parse_type(
						$c->comment,
						array(
							'text_format'	=> 'none',
							'html_format'	=> 'none',
							'auto_links'	=> 'n',
							'allow_img_url' => 'n'
						)
					);

					$comments_swap[] = array(
						'name_of_commenter'	=> $c->name,
						'name'				=> $c->name,
						'comment'			=> $comment_text,
						'comment_id'		=> $c->comment_id,
					);
				}


				$swap = array(
					'channel_name'					=> $results->row('channel_title'),
					'entry_title'					=> $results->row('title'),
					'site_name'						=> stripslashes(ee()->config->item('site_name')),
					'site_url'						=> ee()->config->item('site_url'),
					'comment_url'					=> reduce_double_slashes($com_url.'/'.$results->row('url_title') .'/'),
					'channel_id'					=> $results->row('channel_id'),
					'entry_id'						=> $results->row('entry_id'),
					'url_title'						=> $results->row('url_title'),
					'comment_url_title_auto_path'	=> reduce_double_slashes($com_url.'/'.$results->row('url_title')),

					'comments'						=> $comments_swap
				);

				$template = ee()->functions->fetch_email_template('comments_opened_notification');

				ee()->load->library('template');


				$email_tit = ee()->template->parse_variables_row($template['title'], $swap);
				$email_msg = ee()->template->parse_variables_row($template['data'], $swap);

				//	Send email
				ee()->load->library('email');
				ee()->email->wordwrap = true;

				// Load the text helper
				ee()->load->helper('text');

				$sent = array();

				foreach ($recipients as $val)
				{
					if ( ! in_array($val['0'], $sent))
					{
						$title	 = $email_tit;
						$message = $email_msg;

						$sub	= $subscriptions[$val['1']];
						$sub_qs	= 'id='.$sub['subscription_id'].'&hash='.$sub['hash'];

						// Deprecate the {name} variable at some point
						$title	 = str_replace('{name}', $val['2'], $title);
						$message = str_replace('{name}', $val['2'], $message);

						$title	 = str_replace('{name_of_recipient}', $val['2'], $title);
						$message = str_replace('{name_of_recipient}', $val['2'], $message);

						$title	 = str_replace('{notification_removal_url}', ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $title);
						$message = str_replace('{notification_removal_url}', ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $message);

						ee()->email->EE_initialize();
						ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
						ee()->email->to($val['0']);
						ee()->email->subject($title);
						ee()->email->message(entities_to_ascii($message));
						ee()->email->send();

						$sent[] = $val['0'];
					}
				}
			}
		}

		return;
	}

	// --------------------------------------------------------------------

	/**
	 * Update Entry and Channel Stats
	 *
	 * @return	void
	 */
	public function update_stats($entry_ids, $channel_ids, $author_ids)
	{
		ee()->stats->update_channel_title_comment_stats($entry_ids);

		// Quicker and updates just the channels
		foreach($channel_ids as $channel_id)
		{
			ee()->stats->update_comment_stats($channel_id, '', FALSE);
		}

		// Updates the total stats for the sites table
		ee()->stats->update_comment_stats();

		ee()->stats->update_authors_comment_stats($author_ids);

		return;
	}

	// --------------------------------------------------------------------

	/**
	 * Settings page
	 *
	 * @return	void
	 */
	public function settings()
	{
		$this->_permissions_check();

		ee()->load->library('table');

		$vars = array('action_url' => 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=save_settings'
		);

		ee()->view->cp_page_title = lang('comment_settings');

		ee()->view->cp_breadcrumbs = array(
			$this->base_url => lang('comments')
		);

		$vars['comment_word_censoring']			= (ee()->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE;
		$vars['comment_moderation_override']	= (ee()->config->item('comment_moderation_override') == 'y') ? TRUE : FALSE;
		$vars['comment_edit_time_limit']	= (ee()->config->item('comment_edit_time_limit') && ctype_digit(ee()->config->item('comment_edit_time_limit'))) ? ee()->config->item('comment_edit_time_limit') : 0;

		return ee()->load->view('settings', $vars, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Update Comment Settings
	 *
	 * @return	void
	 */
	public function save_settings()
	{
		$this->_permissions_check();

		$timelimit = ee()->input->post('comment_edit_time_limit');

		$insert['comment_word_censoring'] = (ee()->input->post('comment_word_censoring')) ? 'y' : 'n';
		$insert['comment_moderation_override'] = (ee()->input->post('comment_moderation_override')) ? 'y' : 'n';
		$insert['comment_edit_time_limit'] = ($timelimit && ctype_digit($timelimit)) ? $timelimit : '';

		ee()->config->_update_config($insert);


		ee()->session->set_flashdata('message_success', lang('settings_updated'));

		ee()->functions->redirect($this->base_url.AMP.'method=settings');
	}
}
// END CLASS

// EOF
