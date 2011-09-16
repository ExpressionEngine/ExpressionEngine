<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Comment_mcp {

	protected $pipe_length			= '2';
	protected $comment_chars		= "20";
	protected $comment_leave_breaks = 'n';
	protected $perpage 				= 50;
	protected $base_url 			= '';
	protected $search_url;

	protected $_dir; 
	protected $_limit;
	protected $_offset;
	protected $_order_by;
	protected $_entry_id;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
				
		if (REQ == 'CP')
		{
			$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment';

			if ($this->EE->cp->allowed_group('can_moderate_comments') &&  
				$this->EE->cp->allowed_group('can_edit_all_comments') && 
				$this->EE->cp->allowed_group('can_delete_all_comments'))
			{
				$this->EE->cp->set_right_nav(
					array(
						'settings'	=> $this->base_url.AMP.'method=settings',
						'comments'	=> $this->base_url)
					);	
			}
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

		$this->EE->load->helper(array('text', 'form'));
		$this->EE->load->library('javascript');

		$this->EE->javascript->set_global('lang.selection_required', lang('selection_required'));

		$this->EE->cp->set_variable('cp_page_title', lang('comments'));

		$this->_setup_query_filters();

		list($total_count, $qry) = $this->_setup_index_query();

		if ( ! $qry->num_rows())
		{
			$comments = FALSE;
		}
		else
		{
			$comment = $this->_get_comments($qry->result());
			$channel = $this->_get_channel_info($comment->result());
			$author = $this->_get_author_info($comment->result());

			$comments = $this->_merge_comment_data($comment->result(), $channel, $author);

			$comment->free_result();
			$channel->free_result();
			$author->free_result();
		}

		$data = array(
			'comments'				=> $comments,
			'pagination'			=> $this->_setup_pagination($total_count),
			'channel_select_opts' 	=> $this->_channel_select_opts(),
			'channel_selected'		=> $this->_channel,
			'status_select_opts'	=> $this->_status_select_opts(),
			'status_selected'		=> $this->_status,
			'date_select_opts'		=> $this->_date_select_opts(),
			'date_selected'			=> $this->_date_range,
			'form_options'			=> array(
							'close' 	=> lang('close_selected'),
							'open' 		=> lang('open_selected'),
							'pending' 	=> lang('pending_selected'),
							'null'		=> '------',
							'delete'	=> lang('delete_selected')
			)
		);

		return $this->EE->load->view('index', $data, TRUE);
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
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') && 
			 ! $this->EE->cp->allowed_group('can_edit_all_comments'))
		{
			$query = $this->EE->channel_model->get_channels(
									(int) $this->EE->config->item('site_id'), 
									array('channel_title', 'channel_id', 'cat_group'));
		}
		else
		{
			$this->EE->db->select('channel_title, channel_id, cat_group');
			$this->EE->db->where('site_id', (int) $this->EE->config->item('site_id'));
			$this->EE->db->order_by('channel_title');
		
			$query = $this->EE->db->get('channels'); 
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
		$this->EE->load->library('typography');

		$config = array(
			'parse_images'	=> FALSE,
			'allow_headings'=> FALSE,	
			'word_censor' 	=> ($this->EE->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE
		);

		$this->EE->typography->initialize($config);

		// There a result for authors here, or are they all anon?
		$authors = ( ! $authors->num_rows()) ? array() : $authors->result();

		foreach ($comments as $k => $v)
		{
			// Drop the entry title into the comment object
			foreach ($channels->result() as $row)
			{
				if ($v->entry_id == $row->entry_id)
				{
					$comments[$k]->entry_title = $row->title;

					break;
				}
			}

			// Get member info as well.
			foreach ($authors as $row)
			{
				if ($v->author_id == $row->member_id)
				{
					$comments[$k]->author_screen_name = $row->screen_name;
					break;
				}
			}

			if ( ! isset($comments[$k]->author_screen_name))
			{
				$comments[$k]->author_screen_name = '';
			}

			// Convert stati to human readable form
			switch ($comments[$k]->status)
			{
				case 'o':
					$comments[$k]->status = lang('open');
					break;
				case 'c':
					$comments[$k]->status = lang('closed');
					break;
				default:
					$comments[$k]->status = lang("pending");
			}

			// Alter the email var
			$comments[$k]->email = mailto($comments[$k]->email);

			// Create comment_edit_link
			$comments[$k]->comment_edit_link = sprintf(
					"<a class=\"less_important_link\" href=\"%s\" title=\"%s\">%s</a>",
					$this->base_url.AMP.'method=edit_comment_form'.AMP.'comment_id='.$comments[$k]->comment_id,
					'edit',
					ellipsize($comments[$k]->comment, 50)
				);
			
			$comments[$k]->comment = $this->EE->typography->parse_type($comments[$k]->comment);
		}

		// flip the array
		$comments = array_reverse($comments);

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

		return $this->EE->db->select('member_id, screen_name, username')
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

		return $this->EE->db->select('title, entry_id')
							->where_in('entry_id', $ids)
							->get('channel_titles');
	}

	// --------------------------------------------------------------------

	/**
	 * Setup pagination for the module index page.
	 *
	 * @param 	int 	total number of items
	 * @return 	string 	rendered pagination links to display in the view
	 */
	protected function _setup_pagination($total)
	{
		$this->EE->load->library('pagination');

		$url = $this->base_url.AMP.'method=index';

		if ($this->_channel)
		{
			$url .= AMP.'channel_id='.$this->_channel;
		}

		if ($this->_status && $this->_status != 'all')
		{
			$url .= AMP.'status='.$this->_status;
		}

		if ($this->_date_range)
		{
			$url .= AMP.'status='.$this->_date_range;
		}

		if ($this->_entry_id)
		{
			$url .= AMP.'entry_id='.$this->_entry_id;
		}

		$p_button = "<img src=\"{$this->EE->cp->cp_theme_url}images/pagination_%s_button.gif\" width=\"13\" height=\"13\" alt=\"%s\" />";

		$config = array(
			'base_url'				=> $url,
			'total_rows'			=> $total,
			'per_page'				=> $this->_limit,
			'page_query_string'		=> TRUE,
			'query_string_segment'	=> 'offset',
			'full_tag_open'			=> '<p id="paginationLinks">',
			'full_tag_close'		=> '</p>',
			'prev_link'				=> sprintf($p_button, 'prev', '&lt;'),
			'next_link'				=> sprintf($p_button, 'next', '&gt;'),
			'first_link'			=> sprintf($p_button, 'first', '&lt; &lt;'),
			'last_link'				=> sprintf($p_button, 'last', '&gt; &gt;')
		);

		$this->EE->pagination->initialize($config);

		return $this->EE->pagination->create_links();
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

		// get total number of comments
		$count = (int) $this->EE->db->select('COUNT(*) as count')
									->get_where('comments', array(
							  		'site_id' => (int) $this->EE->config->item('site_id')
								   ))->row('count');

		// get filters
		$this->_query_filters();

		$qry = $this->EE->db->select('comment_id')
							->where('site_id', (int) $this->EE->config->item('site_id'))
							->order_by('comment_date', $this->_dir)
							->get('comments', $this->_limit, $this->_offset);

		return array($count, $qry);
	}

	// --------------------------------------------------------------------	

	protected function _query_filters()
	{
		// If the can ONLY edit their own comments- need to 
		// bring in title table to limit on author		
		if (( ! $this->EE->cp->allowed_group('can_moderate_comments') && 
			  ! $this->EE->cp->allowed_group('can_edit_all_comments')) && 	
				$this->EE->cp->allowed_group('can_edit_own_comments'))
		{
			$this->EE->db->where('author_id', (int) $this->EE->session->userdata('member_id'));
		}

		if ($this->_channel)
		{
			$this->EE->db->where('channel_id', (int) $this->_channel);
		}

		if ($this->_status && $this->_status != 'all')
		{
			$this->EE->db->where('status', $this->_status);
		}

		if ($this->_date_range)
		{
			$date_range = time() - ($this->_date_range * 60 * 60 * 24);
			
			$this->EE->db->where('comment_date >', (int) $date_range);			
		}

		if ($this->_entry_id)
		{
			$this->EE->db->where('entry_id', (int) $this->_entry_id);		
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Comments
	 *
	 * This method takes an array of comment ids and performs the query
	 * based on the filtering that previously happened.  
	 *
	 * @param 	array 	ids of comments to retrieve
	 * @return 	object 	db object
	 */
	protected function _get_comments($ids)
	{
		$comment_ids = array();

		foreach ($ids as $id)
		{
			$comment_ids[] = (int) $id->comment_id;
		}

		return $this->EE->db->where_in('comment_id', $comment_ids)
							->get('comments');
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
	protected function _setup_query_filters()
	{
		$this->_channel = $this->EE->input->get_post('channel_id');
		$this->_status = $this->EE->input->get_post('status');
		$this->_date_range = $this->EE->input->get_post('date_range');

		$this->_limit = ($per_page = $this->EE->input->get('per_page')) ? $per_page : 50;
		$this->_offset = ($offset = $this->EE->input->get('offset')) ? $offset : 0;
		$this->_dir = ($dir = $this->EE->input->get('dir')) ? $dir : 'desc'; 
		$this->_order_by = ($ob = $this->EE->input->get('order_by')) ? $ob : 'comment_date';
		$this->_entry_id = $this->EE->input->get('entry_id');
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment Notification
	 *
	 * @return	string
	 */
	public function delete_comment_notification()
	{
		if ( ! $id = $this->EE->input->get_post('id') OR 
			 ! $hash = $this->EE->input->get_post('hash'))
		{
			return FALSE;
		}

		if ( ! is_numeric($id))
		{
			return FALSE;
		}

		$this->EE->lang->loadfile('comment');

		$this->EE->load->library('subscription');
		$this->EE->subscription->init('comment', array('subscription_id' => $id), TRUE);
		$this->EE->subscription->unsubscribe('', $hash);

		$data = array(
				'title' 	=> lang('cmt_notification_removal'),
				'heading'	=> lang('thank_you'),
				'content'	=> lang('cmt_you_have_been_removed'),
				'redirect'	=> '',
				'link'		=> array($this->EE->config->item('site_url'), stripslashes($this->EE->config->item('site_name')))
		);

		$this->EE->output->show_message($data);
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

		$this->EE->load->library('table');
		$this->EE->load->library('javascript');	

		$this->EE->javascript->output('		

		// If validation fails- want to be sure to show the move field if populated
		if ($("#move_to").val() != "")
		{
			$("#move_link").hide();
			$("#move_field").show();
		}
		
		$("#move_link").click(function() {
			$("#move_link").hide();
			$("#move_field").show();
			return false;
		});
		
		$("#cancel_link").click(function() {
			$("input#move_to").val("");
			$("#move_link").show();
			$("#move_field").hide();
			return false;
		});		
		');


		$this->EE->javascript->compile();
		$comment_id	= ( ! $comment_id) ? $this->EE->input->get_post('comment_id') : $comment_id;


		if ($comment_id == FALSE OR ! is_numeric($comment_id))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->EE->load->helper(array('form', 'snippets'));


		$this->EE->db->select('channel_titles.author_id as entry_author, title, channel_title, comment_require_email, comment, comment_id, comments.author_id, comments.status, name, email, url, location, comments.ip_address, comment_date, channels.comment_text_formatting, channels.comment_html_formatting, channels.comment_allow_img_urls, channels.comment_auto_link_urls');
		$this->EE->db->from(array('channel_titles', 'comments'));
		$this->EE->db->join('channels', 'exp_comments.channel_id = exp_channels.channel_id ', 'left');
		$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
		$this->EE->db->where('comments.comment_id', $comment_id);

		$query = $this->EE->db->get();
		
		if ($query->num_rows() === 0)
		{
			return FALSE;
		}
			
		if ($this->EE->cp->allowed_group('can_edit_all_comments'))
		{
			$can_edit = TRUE;
		}
		else
		{
			if ($query->row('entry_author') == $this->EE->session->userdata('member_id'))
			{
				$can_edit = TRUE;
			}
			else
			{
				if ( ! $this->EE->cp->allowed_group('can_moderate_comments'))
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
		
		$vars['status'] = ($this->EE->input->post('status')) ? $this->EE->input->post('status') : $vars['status'];

		// Instantiate Typography class
		$config = ($this->EE->config->item('comment_word_censoring') == 'y') ? array('word_censor' => TRUE) : array();
		
		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'parse_images'	=> FALSE)
				);

		$vars['display_comment'] = $this->EE->typography->parse_type($vars['comment'],
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

		$this->EE->cp->set_variable('cp_page_title', lang('edit_comment'));

		// a bit of a breadcrumb override is needed
		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base_url => lang('comments')));

		$vars['hidden'] = $hidden;

		$this->EE->javascript->compile();
		
		return $this->EE->load->view('edit', $vars, TRUE);
	}
	
	// --------------------------------------------------------------------

	/**
	 * This permissions check is used in several places.
	 */
	private function _permissions_check()
	{
		if ( ! $this->EE->cp->allowed_group('can_moderate_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_all_comments') 
		  && ! $this->EE->cp->allowed_group('can_edit_own_comments'))
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

		$comment_id = $this->EE->input->get_post('comment_id');

		if ($comment_id == FALSE OR ! is_numeric($comment_id))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->EE->load->library('form_validation');
		$can_edit = FALSE;
		
		if ($this->EE->cp->allowed_group('can_edit_all_comments'))
		{
			$query = $this->EE->db->get_where('comments', array('comment_id' => $comment_id));
			$can_edit = TRUE;
		}
		else
		{
			$this->EE->db->select('channel_titles.author_id, comments.channel_id, comments.entry_id');
			$this->EE->db->from(array('channel_titles', 'comments'));
			$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
			$this->EE->db->where('comments.comment_id', $comment_id);

			$query = $this->EE->db->get();

			if ($query->row('author_id') != $this->EE->session->userdata('member_id'))
			{
				if ( ! $this->EE->cp->allowed_group('can_moderate_comments'))
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
		$this->EE->db->select('channels.comment_require_email');
		$this->EE->db->from(array('channels', 'comments'));
		$this->EE->db->where('comments.channel_id = '.$this->EE->db->dbprefix('channels.channel_id'));
		$this->EE->db->where('comments.comment_id', $comment_id);
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return show_error(lang('no_channel_exists'));
		}

		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		$status = $this->EE->input->post('status');

		//  If they can not edit- only the status may change
		if ( ! $can_edit)
		{
			if ( ! in_array($status, array('o', 'c', 'p')))
			{
				show_error(lang('unauthorized_access'));
			}
			
			$data = array('status' => $status);
			$this->EE->db->query($this->EE->db->update_string('exp_comments', $data, "comment_id = '$comment_id'"));
			
			$this->update_stats(array($entry_id), array($channel_id), array($author_id));

			//  Did status change to open?  Notify
			if ($status == 'o' && $current_status != 'o')
			{
				$this->send_notification_emails(array($comment_id));
			}
			
			$this->EE->functions->clear_caching('all');

			$url = $this->base_url.AMP.'comment_id='.$comment_id;

			$this->EE->session->set_flashdata('message_success',  
												lang('comment_updated'));
			$this->EE->functions->redirect($url);			
		}
		
		// Error checks
		if ($author_id == 0)
		{
			// Fetch language file
			$this->EE->lang->loadfile('myaccount');

			if ($comment_require_email == 'y')
			{
				$this->EE->form_validation->set_rules('email', 'lang:email', 'callback__email_check');
			}
			else
			{
				$this->EE->form_validation->set_rules('email', 'lang:email', '');
			}

			$this->EE->form_validation->set_rules('name', 'lang:name', 'required');
	
		
			$this->EE->form_validation->set_rules('url', '', '');			
			$this->EE->form_validation->set_rules('location', '', '');
		}


		// Are thy moving the comment?  Check for valid entry_id
		$move_to = $this->EE->input->get_post('move_to');
		$recount_ids = array();
		$recount_channels = array();

		if ($move_to != '')
		{
			$tcount = 0;
			
			if (ctype_digit($move_to))
			{
				$this->EE->db->select('title, entry_id, channel_id');
				$this->EE->db->where('entry_id', $move_to);
				$query = $this->EE->db->get('channel_titles');
			
				$tcount = $query->num_rows();
			}


			if ($tcount == 0)
			{
				$this->EE->form_validation->set_rules('move_to', 'lang:move_to', 'callback__move_check');
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

		
		$this->EE->form_validation->set_rules('comment', 'lang:comment', 'required');

		$this->EE->form_validation->set_error_delimiters('<br /><span class="notice">', '<br />');
		
		if ($this->EE->form_validation->run() === FALSE)
		{
			return $this->edit_comment_form($comment_id);
		}		

		// Build query

		if ($author_id == 0)
		{
			$data = array(
							'entry_id' => $new_entry_id,
							'channel_id' => $new_channel_id,
							'name'		=> $this->EE->input->post('name'),
							'email'		=> $this->EE->input->post('email'),
							'url'		=> $this->EE->input->post('url'),
							'location'	=> $this->EE->input->post('location'),
							'comment'	=> $this->EE->input->post('comment'),
							'status'	=> $status
						 );
		}
		else
		{
			$data = array(
							'entry_id' => $new_entry_id,
							'channel_id' => $new_channel_id,
							'comment'	=> $this->EE->input->post('comment'),
							'status'	=> $status
						 );
		}
		
		$this->EE->db->query($this->EE->db->update_string('exp_comments', $data, "comment_id = '$comment_id'"));

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
			$this->EE->load->model('comment_model');

			$this->EE->comment_model->recount_entry_comments($recount_ids);
			
			// Quicker and updates just the channels
			foreach(array_unique($recount_channels) as $channel_id) 
			{ 
				$this->EE->stats->update_comment_stats($channel_id, '', FALSE); 
			}

			// Updates the total stats
			$this->EE->stats->update_comment_stats();
		}
		

		/* -------------------------------------------
		/* 'update_comment_additional' hook.
		/*  - Add additional processing on comment update.
		*/
			$edata = $this->EE->extensions->call('update_comment_additional', $comment_id, $data);
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/
		
		$this->EE->functions->clear_caching('all');

		$url = $this->base_url.AMP.'comment_id='.$comment_id;

		$this->EE->session->set_flashdata('message_success',  lang('comment_updated'));
		$this->EE->functions->redirect($url);
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
			$this->EE->form_validation->set_message('_email_check', 	
												lang('missing_email'));
			return FALSE;

		}

		// Is email valid?
		$this->EE->load->helper('email');
		
		if ( ! valid_email($str))
		{
			$this->EE->form_validation->set_message('_email_check', 
												lang('invalid_email_address'));
			return FALSE;
		}

		// Is email banned?
		if ($this->EE->session->ban_check('email', $str))
		{
			$this->EE->form_validation->set_message('_email_check', 
												lang('banned_email'));
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
		$this->EE->form_validation->set_message('_move_check', 
												lang('invalid_entry_id'));
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
		if ( ! $this->EE->input->post('toggle') && 
			 ! $this->EE->input->get_post('comment_id'))
		{
			$this->EE->session->set_flashdata('message_failure', 
											lang('no_valid_selections'));
			$this->EE->functions->redirect($this->base_url);
		}

		switch($this->EE->input->post('action'))
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
		if ( ! $this->EE->cp->allowed_group('can_delete_all_comments') 
		  && ! $this->EE->cp->allowed_group('can_delete_own_comments'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->EE->cp->get_installed_modules();
		
		$blacklist_installed =  (isset($this->EE->cp->installed_modules['blacklist'])) ? TRUE : FALSE;

		if ( ! $this->EE->input->post('toggle') && ! $this->EE->input->get_post('comment_id'))
		{
			$this->EE->session->set_flashdata('message_failure', lang('no_valid_selections'));
			$this->EE->functions->redirect($this->base_url);
		}

		$this->EE->load->library('table');
		$comments = array();

		if ($this->EE->input->post('toggle'))
		{
			foreach ($_POST['toggle'] as $key => $val)
			{
				$comments[] = $val;
			}
		}
		
		if ($this->EE->input->get_post('comment_id') !== FALSE && is_numeric($this->EE->input->get_post('comment_id')))
		{
			$comments[] = $this->EE->input->get_post('comment_id');
		}

		if (count($comments) == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$this->EE->db->select('channel_titles.author_id, title, comments.comment_id, comment, comments.ip_address');
		$this->EE->db->from(array('channel_titles', 'comments'));
		$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
		$this->EE->db->where_in('comments.comment_id', $comments);

		$comments	= array();

		$query = $this->EE->db->get();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				if ( ! $this->EE->cp->allowed_group('can_delete_all_comments')  && ($row['author_id'] != $this->EE->session->userdata('member_id')))
				{					
					continue;
				}
				
				$row['comment'] = strip_tags(str_replace(array("\t","\n","\r"), ' ', $row['comment']));
				$row['comment'] = $this->EE->functions->char_limiter(trim($row['comment']), 100);


				$comments[$row['comment_id']]['entry_title'] = $row['title'];
				$comments[$row['comment_id']]['comment'] = $row['comment'];
				$comments[$row['comment_id']]['ip_address'] = $row['ip_address'];
			}
		}

		if (count($comments) == 0)
		{
			$this->EE->session->set_flashdata('message_failure', 
											lang('no_valid_selections'));
			$this->EE->functions->redirect($this->base_url);
		}

		$this->EE->load->helper('form');
		$this->EE->cp->set_variable('cp_page_title', lang('delete_confirm'));

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base_url => lang('comments'),

		));
		
		$vars = array();

		$vars['hidden'] = array(
					'comment_ids'	=> implode('|', array_keys($comments)));
								
		$vars['blacklist_installed'] = (isset($this->EE->cp->installed_modules['blacklist'])) ? TRUE : FALSE;
								
		$message = (count($comments) > 1) ? 'delete_comments_confirm' : 'delete_comment_confirm';

		$vars['comments'] = $comments;
		$vars['message'] = $message;
		
		return $this->EE->load->view('delete_comments', $vars, TRUE);
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

		if($this->EE->input->get_post('comment_id') !== FALSE && is_numeric($this->EE->input->get_post('comment_id')))
		{
			$comments[$this->EE->input->get_post('comment_id')] = $this->EE->input->get_post('comment_id');
		}

		if (count($comments) == 0)
		{
			show_error(lang('unauthorized_access'));
		}

		$status = ($status == '') ? $this->EE->input->get('status') : $status;

		if ( ! in_array($status, array('o', 'c', 'p')))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->EE->db->select('exp_comments.entry_id, exp_comments.channel_id, exp_comments.author_id, comment_id, exp_channel_titles.author_id AS entry_author');
		$this->EE->db->join('channel_titles', 'exp_comments.entry_id = exp_channel_titles.entry_id', 'left');
		$this->EE->db->where_in('comment_id', $comments);
		$query = $this->EE->db->get('comments');

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
			if (( ! $this->EE->cp->allowed_group('can_moderate_comments') 
			   && ! $this->EE->cp->allowed_group('can_edit_all_comments')) 
			   && ($row['entry_author'] != $this->EE->session->userdata('member_id')))
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

		$this->EE->db->set('status', $status);
		$this->EE->db->where_in('comment_id', $comments);
		$this->EE->db->update('comments');
		
		$this->update_stats($entry_ids, $channel_ids, $author_ids);
		
		//	 Send email notification or remove notifications

		if ($status == 'o')
		{
			$this->send_notification_emails($comments);
		}
		
		if ($this->EE->extensions->active_hook('update_comment_additional'))
		{

			$qry = $this->EE->db->where_in('comment_id', $comments)
								->get('comments');
			
			foreach ($qry->result_array() as $row)
			{
				/* -------------------------------------------
				/* 'update_comment_additional' hook.
				/*  - Add additional processing on comment update.
				*/
					$edata = $this->EE->extensions->call(
													'update_comment_additional', 
													$row['comment_id'], $row
												);

					if ($this->EE->extensions->end_script === TRUE) return;
				/*
				/* -------------------------------------------*/
			}
		}

		$this->EE->functions->clear_caching('all');

		$url = $this->base_url;

		$this->EE->session->set_flashdata('message_success', lang('status_changed'));
		$this->EE->functions->redirect($url);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment
	 *
	 * @return	void
	 */
	public function delete_comment()
	{
		if ( ! $this->EE->cp->allowed_group('can_delete_all_comments') && 
			 ! $this->EE->cp->allowed_group('can_delete_own_comments'))
		{
			show_error(lang('unauthorized_access'));
		}

		$comment_id = $this->EE->input->post('comment_ids');

		if ($comment_id == FALSE)
		{
			show_error(lang('unauthorized_access'));
		}


		if ( ! preg_match("/^[0-9]+$/", str_replace('|', '', $comment_id)))
		{
			show_error(lang('unauthorized_access'));
		}
		
		$this->EE->db->where_in('comment_id', explode('|', $comment_id));
		$count = $this->EE->db->count_all_results('comments');

		if ($count == 0)
		{
			show_error(lang('unauthorized_access'));
		}
	
		$this->EE->cp->get_installed_modules();
		
		$blacklist_installed =  (isset($this->EE->cp->installed_modules['blacklist'])) ? TRUE : FALSE;

		$this->EE->db->select('channel_titles.author_id, channel_titles.entry_id, channel_titles.channel_id, channel_titles.comment_total, comments.ip_address');
		$this->EE->db->from(array('channel_titles', 'comments'));
		$this->EE->db->where('channel_titles.entry_id = '.$this->EE->db->dbprefix('comments.entry_id'));
		$this->EE->db->where_in('comments.comment_id', explode('|', $comment_id));

		$query = $this->EE->db->get();

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


		if ( ! $this->EE->cp->allowed_group('can_delete_all_comments'))
		{
			foreach($query->result_array() as $row)
			{
				if ($row['author_id'] != $this->EE->session->userdata('member_id'))
				{
					show_error(lang('unauthorized_access'));
				}
			}
		}

		// If blacklist was checked- blacklist!
		if ($blacklist_installed && $this->EE->input->post('add_to_blacklist') == 'y')
		{
			include_once PATH_MOD.'blacklist/mcp.blacklist.php';

			$bl = new Blacklist_mcp();
			
			// Write to htaccess?
			$write_htacces = ($this->EE->session->userdata('group_id') == '1' && $this->EE->config->item('htaccess_path') != '')	? TRUE : FALSE;		
			
			$blacklisted = $bl->update_blacklist($ips, $write_htacces, 'bool');
		}


		$comment_ids = explode('|', $comment_id);

		/* -------------------------------------------
		/* 'delete_comment_additional' hook.
		/*  - Add additional processing on comment delete
		*/
			$edata = $this->EE->extensions->call('delete_comment_additional', $comment_ids);
			if ($this->EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

		$this->EE->db->where_in('comment_id', $comment_ids);
		$this->EE->db->delete('comments');
		
		$this->update_stats($entry_ids, $channel_ids, $author_ids);

		$this->EE->functions->clear_caching('all');
		$this->EE->session->set_flashdata('message_success', 
										  lang('comment_deleted'));

		$this->EE->functions->redirect($this->base_url);
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
		$this->EE->load->library('subscription');
			
		// Instantiate Typography class
		$this->EE->load->library('typography');
		$this->EE->typography->initialize(array(
				'parse_images'		=> FALSE,
				'word_censor'		=> ($this->EE->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE)
				);


		// Grab the required comments
		$this->EE->db->select('comment, comment_id, author_id, name, email, comment_date, entry_id');
		$this->EE->db->where_in('comment_id', $comments);
		$query = $this->EE->db->get('comments');


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
			$this->EE->subscription->init('comment', array('entry_id' => $entry_id), TRUE);
			
			// Grab them all
			$subscriptions = $this->EE->subscription->get_subscriptions();
			
			$this->EE->load->model('comment_model');
			$recipients = $this->EE->comment_model->fetch_email_recipients($entry_id, $subscriptions);
			
			if (count($recipients))
			{
				// Grab generic entry info
				
				$action_id	= $this->EE->functions->fetch_action_id('Comment_mcp', 'delete_comment_notification');

				$this->EE->db->select('channel_titles.title, channel_titles.entry_id, channel_titles.url_title, channels.channel_title, channels.comment_url, channels.channel_url, channels.channel_id');
				$this->EE->db->join('channels', 'exp_channel_titles.channel_id = exp_channels.channel_id', 'left');
				$this->EE->db->where('channel_titles.entry_id', $entry_id);
				$results = $this->EE->db->get('channel_titles');		

				$com_url = ($results->row('comment_url')  == '') ? $results->row('channel_url')	 : $results->row('comment_url');				
				
				
				// Create an array of comments to add to the email
				
				$comments_swap = array();
				
				foreach ($comments as $c)
				{
					$comment_text = $this->EE->typography->parse_type(
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
					'site_name'						=> stripslashes($this->EE->config->item('site_name')),
					'site_url'						=> $this->EE->config->item('site_url'),
					'comment_url'					=> $this->EE->functions->remove_double_slashes($com_url.'/'.$results->row('url_title') .'/'),
					'channel_id'					=> $results->row('channel_id'),
					'entry_id'						=> $results->row('entry_id'),
					'url_title'						=> $results->row('url_title'),
					'comment_url_title_auto_path'	=> reduce_double_slashes($com_url.'/'.$results->row('url_title')),
					
					'comments'						=> $comments_swap
				);
				
				$template = $this->EE->functions->fetch_email_template('comments_opened_notification');
				
				$this->EE->load->library('template');
				
				
				$email_tit = $this->EE->template->parse_variables_row($template['title'], $swap);
				$email_msg = $this->EE->template->parse_variables_row($template['data'], $swap);

				//	Send email
				$this->EE->load->library('email');
				$this->EE->email->wordwrap = true;

				// Load the text helper
				$this->EE->load->helper('text');

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

						$title	 = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $title);
						$message = str_replace('{notification_removal_url}', $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&'.$sub_qs, $message);

						$this->EE->email->EE_initialize();
						$this->EE->email->from($this->EE->config->item('webmaster_email'), $this->EE->config->item('webmaster_name'));
						$this->EE->email->to($val['0']);
						$this->EE->email->subject($title);
						$this->EE->email->message(entities_to_ascii($message));
						$this->EE->email->send();

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
		foreach($entry_ids as $entry_id)
		{
			$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments WHERE status = 'o' AND entry_id = '".$this->EE->db->escape_str($entry_id)."'");

			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;

			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE entry_id = '".$this->EE->db->escape_str($entry_id)."' AND status = 'o'");

			$this->EE->db->set('comment_total', $query->row('count'));
			$this->EE->db->set('recent_comment_date', $comment_date);
			$this->EE->db->where('entry_id', $entry_id);
			$this->EE->db->update('channel_titles');
		}

		// Quicker and updates just the channels
		foreach($channel_ids as $channel_id)
		{
			$this->EE->stats->update_comment_stats($channel_id, '', FALSE);
		}

		// Updates the total stats
		$this->EE->stats->update_comment_stats();

		foreach($author_ids as $author_id)
		{
			$res = $this->EE->db->query("SELECT COUNT(comment_id) AS comment_total, MAX(comment_date) AS comment_date FROM exp_comments WHERE author_id = '$author_id'");
			$resrow = $res->row_array();

			$comment_total = $resrow['comment_total'] ;
			$comment_date  = ( ! empty($resrow['comment_date'])) ? $resrow['comment_date'] : 0;

			$this->EE->db->query($this->EE->db->update_string('exp_members', array('total_comments' => $comment_total, 'last_comment_date' => $comment_date), "member_id = '$author_id'"));
		}
		
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

		$this->EE->load->library('table');
		$this->EE->load->library('javascript');
		$this->EE->load->helper('form');

		$vars = array('action_url' => 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=save_settings'
		);

		$this->EE->cp->set_variable('cp_page_title', lang('comment_settings'));

		$this->EE->cp->set_variable('cp_breadcrumbs', array(
			$this->base_url => lang('comments')));		
		
		$vars['comment_word_censoring']			= ($this->EE->config->item('comment_word_censoring') == 'y') ? TRUE : FALSE;
		$vars['comment_moderation_override']	= ($this->EE->config->item('comment_moderation_override') == 'y') ? TRUE : FALSE;
		$vars['comment_edit_time_limit']	= ($this->EE->config->item('comment_edit_time_limit') && ctype_digit($this->EE->config->item('comment_edit_time_limit'))) ? $this->EE->config->item('comment_edit_time_limit') : 0;		

		return $this->EE->load->view('settings', $vars, TRUE);		
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

		$timelimit = $this->EE->input->post('comment_edit_time_limit');
		
		$insert['comment_word_censoring'] = ($this->EE->input->post('comment_word_censoring')) ? 'y' : 'n';
		$insert['comment_moderation_override'] = ($this->EE->input->post('comment_moderation_override')) ? 'y' : 'n';
		$insert['comment_edit_time_limit'] = ($timelimit && ctype_digit($timelimit)) ? $timelimit : '';
		
		$this->EE->config->_update_config($insert);


		$this->EE->session->set_flashdata('message_success', lang('settings_updated'));

		$this->EE->functions->redirect($this->base_url.AMP.'method=settings');
	}
}
// END CLASS

/* End of file mcp.comment.php */
/* Location: ./system/expressionengine/modules/comment/mcp.comment.php */