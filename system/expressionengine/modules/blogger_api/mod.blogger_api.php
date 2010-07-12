<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Blogger API Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Blogger_api {

	var $return_data	= ''; 						// Bah!
	var $LB				= "\r\n";					// Line Break for Entry Output

	var $status			= '';						// Retrieving
	var $channel			= '';
	var $categories		= '';
	var $fields			= array();
	var $userdata		= array();

	var $title			= 'Blogger API Entry';		// Default Title
	var $channel_id		= '1';						// Default Channel ID
	var $site_id		= '1';						// Default Site ID
	var $field			= '';						// Default Field ID
	var $field_name		= 'body';					// Default Field Name
	var $ecategories 	= array();					// Categories (new/edit entry)
	var $cat_output		= 'name';					// (id, name) ID or Name Outputted?
	var $assign_parents	= TRUE;						// Assign cat parents to post
	var $cat_parents	= array();					// Parent categories of new/edited entry

	var $pref_name		= 'Default';				// Name of preference configuration
	var $block_entry	= FALSE;					// Send entry as one large block, no fields?
	var $field_id		= '2';						// Configuration's Default Field ID
	var $parse_type		= TRUE;						// Use Typography class when sending entry?
	var $text_format	= FALSE;					// Use field's text format with Typography class?
	var $html_format	= 'safe';					// safe, all, none

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Blogger_api()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Make sure benchmark data is turned of so XML is clean
		$this->EE->output->enable_profiler(FALSE);

		$this->EE->lang->loadfile('blogger_api');

		$id = ( isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : '1';

		/** ----------------------------------------
		/**  Configuration Options
		/** ----------------------------------------*/

		$query = $this->EE->db->get_where('blogger', array('blogger_id' => $id));

		if ($query->num_rows() > 0)
		{
			foreach($query->row_array() as $name => $pref)
			{
				$name = str_replace('blogger_', '', $name);

				if ($pref == 'y' OR $pref == 'n')
				{
					$this->{$name} = ($pref == 'y') ? TRUE : FALSE;
				}
				elseif($name == 'field_id')
				{
					$x = explode(':',$pref);
					$this->field_id = ( ! isset($x['1'])) ? $x['0'] : $x['1'];
				}
				else
				{
					$this->{$name} = $pref;
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Incoming Blogger API Requests
	 *
	 * @access	public
	 * @return	void
	 */

	function incoming()
	{
		/** ---------------------------------
		/**  Load the XML-RPC Server
		/** ---------------------------------*/

		$this->EE->load->library('xmlrpc');
		$this->EE->load->library('xmlrpcs');
		
		$this->EE->xmlrpc->set_debug(TRUE);

		/* ---------------------------------
		/*  Specify Functions
		/*	Normally, we would add a signature and docstring to the array for
		/*	each function, but since these are widespread and well known
		/*	functions I just skipped it.
		/* ---------------------------------*/

		$functions = array(	'blogger.getUserInfo'		=> array('function' => 'Blogger_api.getUserInfo'),
							'blogger.getUsersBlogs'		=> array('function' => 'Blogger_api.getUsersBlogs'),
							'blogger.newPost'			=> array('function' => 'Blogger_api.newPost'),
							'blogger.getRecentPosts'	=> array('function' => 'Blogger_api.getRecentPosts'),
							'blogger.getPost'			=> array('function' => 'Blogger_api.getPost'),
							'blogger.editPost'			=> array('function' => 'Blogger_api.editPost'),
							'blogger.deletePost'		=> array('function' => 'Blogger_api.deletePost')
							);

		/** ---------------------------------
		/**  Instantiate the Server Class
		/** ---------------------------------*/

		$this->EE->xmlrpcs->initialize(array('functions' => $functions, 'object' => $this, 'xss_clean' => FALSE));
		$this->EE->xmlrpcs->serve();
	}

	// --------------------------------------------------------------------

	/**
	 * Send user information
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */

	function getUserInfo($plist)
	{
		$this->EE->load->library('xmlrpc');

		$parameters = $plist->output_parameters();

		if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		$response = array(
						array(
								'nickname' =>
								array($this->userdata['screen_name'],'string'),
								'userid' =>
								array($this->userdata['member_id'],'string'),
								'url' =>
								array($this->userdata['url'],'string'),
								'email' =>
								array($this->userdata['email'],'string'),
								'lastname' =>
								array('','string'),
								'firstname' =>
								array('','string')
						),
						'struct'
					);

		return $this->EE->xmlrpc->send_response($response);
	}

	// --------------------------------------------------------------------

	/**
	 * Get user's blogs
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */

	function getUsersBlogs($plist)
	{
		$this->EE->load->library('xmlrpc');

		$parameters = $plist->output_parameters();

		if ( ! $this->fetch_member_data($parameters['1'], $parameters['2']))
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		$this->EE->db->select('channel_id, channel_title, channel_url');
		$this->EE->db->where_in('channel_id', $this->userdata['assigned_channels']);
		$query = $this->EE->db->get('channels');

		if ($query->num_rows() == 0)
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('no_channels_found'));
		}

		$response = array();

		foreach($query->result_array() as $row)
		{
			$channel = array(array(
									"url" =>
									array($row['channel_url'],"string"),
									"blogid" =>
									array($row['channel_id'],"string"),
									"channelName" =>
									array($row['channel_title'],"string")),'struct');

			array_push($response, $channel);
		}

		return $this->EE->xmlrpc->send_response(array($response, 'array'));
	}

	// --------------------------------------------------------------------

	/**
	 * Get recent posts
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */

	function getRecentPosts($plist, $entry_id = '')
	{
		$this->EE->load->library('xmlrpc');

		$parameters = $plist->output_parameters();

		if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		if ( ! $this->userdata['can_access_content'])
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		/** ---------------------------------------
		/**  Parse Out Channel Information
		/** ---------------------------------------*/

		if ($entry_id == '')
		{
			$this->parse_channel($parameters['1']);

			$limit = ( ! isset($parameters['4']) OR $parameters['4'] == '0') ? '10' : $parameters['4'];
		}

		/** ---------------------------------------
		/**  Perform Query
		/** ---------------------------------------*/

		$this->EE->db->select('exp_channel_titles.entry_id, exp_channel_titles.title, exp_channel_titles.channel_id, exp_channel_titles.author_id, exp_channel_titles.entry_date, exp_channel_data.*');
		$this->EE->db->distinct();

		if ($this->categories != '' && $this->categories != 'none')
		{
			$this->EE->db->join('category_posts', 'channel_titles.entry_id = category_posts.entry_id', 'inner');
		}

		$this->EE->db->where('exp_channel_titles.entry_id = exp_channel_data.entry_id');

		if ($this->userdata['group_id'] != '1' && ! $this->userdata['can_edit_other_entries'])
		{
			$this->EE->db->where('exp_channel_titles.author_id', $this->userdata['member_id']);
		}

		if ($entry_id != '')
		{
			$this->EE->db->where('exp_channel_titles.entry_id', $entry_id);
		}
		else
		{
			$this->EE->db->where('exp_channel_titles.channel_id', $this->channel_id);
		}

		if ($this->categories != '' && $this->categories != 'none')
		{
			$this->EE->functions->ar_andor_string($this->categories, 'exp_category_posts.cat_id');
		}

		if ($this->status != '')
		{
			$this->EE->functions->ar_andor_string($this->status, 'exp_channel_titles.status');
		}

		if ($entry_id == '')
		{
			$this->EE->db->order_by('entry_date');
			$this->EE->db->limit($limit, 0);
		}

		$this->EE->db->from('channel_titles');
		$this->EE->db->from('channel_data');

		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('no_entries_found'));
		}

		if ($entry_id != '')
		{
			$this->parse_channel($query->row('channel_id'));
		}

		/** ----------------------------------------
		/**  Instantiate Typography class
		/** ----------------------------------------*/

	  	if ($this->parse_type === TRUE)
	  	{
			$this->EE->load->library('typography');
			$this->EE->typography->initialize();
			$this->EE->typography->parse_images = FALSE;

			$this->EE->typography->encode_email = FALSE;
		}

		/** ---------------------------------------
		/**  Process Output
		/** ---------------------------------------*/

		$response = array();

		foreach($query->result_array() as $row)
		{
			$entry_content  = '<title>'.$row['title'].'</title>';

			// Fields:  Textarea and Text Input Only

			foreach($this->fields as $field_id => $field_data)
			{
				if (isset($row['field_id_'.$field_id]))
				{
					$field_content = $row['field_id_'.$field_id];

					if ($this->parse_type === TRUE)
					{
						$field_content = $this->EE->typography->parse_type($field_content,
																 array(	'text_format'	=> ($this->text_format === FALSE) ? 'none' : $field_data['1'],
																 		'html_format'	=> $this->html_format,
																 		'auto_links'	=> 'n',
																 		'allow_img_url'	=> 'n'
																 		)
																 );
					}

					if ($this->block_entry === TRUE)
					{
						$entry_content .= (trim($field_content) != '') ? $this->LB.$field_content : '';
					}
					else
					{
						$entry_content .= $this->LB."<{$field_data['0']}>".$field_content."</{$field_data['0']}>";
					}
				}
			}

			// Categories

			$cat_array = array();

			$this->EE->db->select('exp_categories.cat_name, exp_categories.cat_id');
			$this->EE->db->join('categories', 'categories.cat_id = category_posts.cat_id');
			($this->cat_output == 'name') ? $this->EE->db->order_by('exp_categories.cat_name') : $this->EE->db->order_by('exp_categories.cat_id');
			$results = $this->EE->db->get_where('category_posts', array('exp_category_posts.entry_id' => $row['entry_id']));

			if ($results->num_rows() > 0)
			{
				foreach($results->result_array() as $rrow)
				{
					$cat_array[] = ($this->cat_output == 'name') ? $rrow['cat_name'] : $rrow['cat_id'];
				}
			}

			$cats = (count($cat_array) > 0) ? implode('|', $cat_array) : '';
			$entry_content .= ($this->block_entry === TRUE) ? '' : $this->LB."<category>".$cats."</category>";

			// Entry Data to XML-RPC form

			$entry_data = array(array(
										'userid' =>
										array($row['author_id'],'string'),
										'dateCreated' =>
										array(date('Y-m-d\TH:i:s',$row['entry_date']).'+00:00','dateTime.iso8601'),
										'blogid' =>
										array($row['channel_id'],'string'),
										'content' =>
										array($entry_content,'string'),
										'postid' =>
										array($row['entry_id'],'string'),
										'category' =>
										array($cats,'string'),
										),
									'struct');

			array_push($response, $entry_data);
		}

		if ($entry_id != '')
		{
			return $this->EE->xmlrpc->send_response($entry_data);
		}
		else
		{
			return $this->EE->xmlrpc->send_response(array($response, 'array'));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get post
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */
	
	function getPost($plist)
	{
		$parameters = $plist->output_parameters();

		return $this->getRecentPosts($plist, $parameters['1']);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete post
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */

	function deletePost($plist)
	{
		$this->EE->load->library('xmlrpc');

		$parameters = $plist->output_parameters();

		if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		if ($this->userdata['group_id'] != '1' AND
			 ! $this->userdata['can_delete_self_entries'] AND
			 ! $this->userdata['can_delete_all_entries'])
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		/** ---------------------------------------
		/**  Retrieve Entry Information
		/** ---------------------------------------*/

		$this->EE->db->select('channel_id, author_id, entry_id');
		$query = $this->EE->db->get_where('channel_titles', array('entry_id' => $parameters['1']));

		if ($query->num_rows() == 0)
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('no_entry_found'));
		}

		/** ---------------------------------------
		/**  Check Delete Privileges
		/** ---------------------------------------*/

		if ($this->userdata['group_id'] != '1')
		{
			if ( ! in_array($query->row('channel_id') , $this->userdata['allowed_channels']))
			{
				return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('unauthorized_action'));
			}

			if ($query->row('author_id')  == $this->userdata['member_id'])
			{
				if ( ! $this->userdata['can_delete_self_entries'])
				{
					return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('unauthorized_action'));
				}
			}
			else
			{
				if ( ! $this->userdata['can_delete_all_entries'])
				{
					return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('unauthorized_action'));
				}
			}
		}

		/** ---------------------------------------
		/**  Perform Deletion
		/** ---------------------------------------*/

		$this->EE->db->where('entry_id', $query->row('entry_id'));
		$this->EE->db->delete('channel_titles');

		$this->EE->db->where('entry_id', $query->row('entry_id'));
		$this->EE->db->delete('channel_data');

		$this->EE->db->where('entry_id', $query->row('entry_id'));
		$this->EE->db->delete('category_posts');

		$this->EE->db->set('total_entries', 'total_entries - 1', FALSE);
		$this->EE->db->where('member_id', $query->row('author_id'));
		$this->EE->db->update('members');

		$conditions = array(
			'status'	   => 'o',
			'entry_id'	   => $query->row('entry_id'),
			'author_id !=' => '0'
		);

		$this->EE->db->select('author_id');
		$results = $this->EE->db->get_where('comments', $conditions);

		if ($results->num_rows() > 0)
		{
			foreach($results->result_array() as $row)
			{
				$conditions = array(
					'status'	=> 'o',
					'entry_id'	=> $query->row('entry_id'),
					'author_id'	=> $row['author_id']
				);

				$this->EE->db->where($conditions);
				$count = $this->EE->db->count_all_results('comments');

				$this->EE->db->set('total_comments', 'total_comments - '.$count, FALSE);
				$this->EE->db->where('member_id', $row['author_id']);
				$this->EE->db->update('members');
			}
		}

		$this->EE->db->where('entry_id', $query->row('entry_id'));
		$this->EE->db->delete('comments');

		$this->EE->stats->update_channel_stats($query->row('channel_id'));
		$this->EE->stats->update_comment_stats($query->row('channel_id'));

		return $this->EE->xmlrpc->send_response(array(1,'boolean'));
	}


	// --------------------------------------------------------------------

	/**
	 * Submit New Post
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */

	function newPost($plist)
	{
		$this->EE->load->library('xmlrpc');
		$this->EE->load->library('api');
		$this->EE->api->instantiate(array('channel_entries', 'channel_categories'));

		$parameters = $plist->output_parameters();

		if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		/** ---------------------------------------
		/**  Parse Out Channel Information
		/** ---------------------------------------*/

		$this->parse_channel($parameters['1']);

		$this->status = ($parameters['5'] == '0') ? 'closed' : 'open';
		$sticky = 'n';

		/** ---------------------------------------
		/**  Parse Channel Meta-Information
		/** ---------------------------------------*/

		// using entities because of <title> conversion by xss_clean()
		if (preg_match('/&lt;title&gt;(.+?)&lt;\/title&gt;/is', $parameters['4'], $matches))
		{
			// Load the text helper
			$this->EE->load->helper('text');

			$this->title = ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities(trim($matches['1'])) : $matches['1'];
			$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
		}

		if (preg_match('/<channel_id>(.+?)<\/channel_id>/is', $parameters['4'], $matches))
		{
			$this->channel_id = trim($matches['1']);
			$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
			$this->parse_channel($this->channel_id);
		}

		if (preg_match('/<category>(.*?)<\/category>/is', $parameters['4'], $matches))
		{
			$this->categories = trim($matches['1']);
			$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);

			if (strlen($this->categories) > 0)
			{
				$this->check_categories($this->channel_id);
			}
		}

		if (preg_match('/<sticky>(.+?)<\/sticky>/is', $parameters['4'], $matches))
		{
			$sticky = (trim($matches['1']) == 'yes' OR trim($matches['1']) == 'y') ? 'y' : 'n';
			$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
		}

		/** ---------------------------------------
		/**  Default Channel Data for channel_id
		/** ---------------------------------------*/

		$this->EE->db->select('deft_comments, cat_group, channel_title, channel_url, channel_notify_emails, channel_notify, comment_url');
		$query = $this->EE->db->get_where('channels', array('channel_id' => $this->channel_id));

		if ($query->num_rows() == 0)
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_channel'));
		}


		/** ---------------------------------
		/**  Build our query string
		/** ---------------------------------*/

		$metadata = array(
							'channel_id'		=> $this->channel_id,
							'author_id'			=> $this->userdata['member_id'],
							'title'				=> $this->title,
							'ip_address'		=> $this->EE->input->ip_address(),
							'entry_date'		=> $this->EE->localize->now,
							'edit_date'			=> gmdate("YmdHis", $this->EE->localize->now),
							'year'			  	=> gmdate('Y', $this->EE->localize->now),
							'month'			 	=> gmdate('m', $this->EE->localize->now),
							'day'				=> gmdate('d', $this->EE->localize->now),
							'sticky'			=> $sticky,
							'status'			=> $this->status

						  );

		/** ---------------------------------------
		/**  Parse Channel Field Data
		/** ---------------------------------------*/

		$entry_data = array('channel_id' => $this->channel_id);

		if (count($this->fields) > 0)
		{
			foreach($this->fields as $field_id => $afield)
			{
				if (preg_match('/<'.$afield['0'].'>(.+?)<\/'.$afield['0'].'>/is', $parameters['4'], $matches))
				{
					if ( ! isset($entry_data['field_id_'.$field_id]))
					{
						$entry_data['field_id_'.$field_id] = $matches['1'];
						$entry_data['field_ft_'.$field_id] = $afield['1'];
					}
					else
					{
						$entry_data['field_id_'.$field_id] .= "\n". $matches['1'];
					}

					$parameters['4'] = trim(str_replace($matches['0'], '', $parameters['4']));
				}
			}
		}

		if (trim($parameters['4']) != '')
		{
			if ( ! isset($entry_data[$this->field]))
			{
				$entry_data['field_id_'.$this->field] = trim($parameters['4']);
				$entry_data['field_ft_'.$this->field] = $this->fields[$this->field]['1'];
			}
			else
			{
				$entry_data[$this->field] .= "\n".trim($parameters['4']);
			}
		}
		
		$metadata['site_id'] = $this->site_id;

		$data = array_merge($metadata, $entry_data, $this->ecategories);

		/** ---------------------------------
		/**  Insert the entry data
		/** ---------------------------------*/

		$success = $this->EE->api_channel_entries->submit_new_entry($this->channel_id, $data);
	
		if ( ! $success)
		{
			$msg = implode(',', $this->EE->api_channel_entries->errors);
			
			return $this->EE->xmlrpc->send_error_message('802', $msg);
			// return $this->EE->xmlrpc->send_response(array($msg, 'string'));
		}

		//  Return Entry ID of new entry
		return $this->EE->xmlrpc->send_response(array($this->EE->api_channel_entries->entry_id, 'string'));
	}

	// --------------------------------------------------------------------

	/**
	 * Edit Post
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */

	function editPost($plist)
	{
		$this->EE->load->library('xmlrpc');

		$parameters = $plist->output_parameters();

		if ( ! $this->fetch_member_data($parameters['2'], $parameters['3']))

		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		if ( ! $this->userdata['can_access_content'])
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
		}

		if ( ! $this->userdata['can_edit_other_entries'])
		{
			// If there aren't any channels assigned to the user, bail out

			if (count($this->userdata['allowed_channels']) == 0)
			{
				return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_access'));
			}
		}


		/** ---------------------------------------
		/**  Details from Parameters
		/** ---------------------------------------*/

		$entry_id = $parameters['1'];

		$this->status = ($parameters['5'] == '0') ? 'closed' : 'open';
		$sticky = 'n';

		/** ---------------------------------------
		/**  Retrieve Entry Information
		/** ---------------------------------------*/

		$this->EE->db->select('channel_id, author_id, title');
		$this->EE->db->where('entry_id', $entry_id);
		$query = $this->EE->db->get('channel_titles');

		if ($query->num_rows() == 0)
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('no_entry_found'));
		}

		if ( ! $this->userdata['can_edit_other_entries'])
		{
			if ($query->row('author_id')  != $this->userdata['member_id'])
			{
				return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('entry_uneditable'));
			}
		}

		$this->channel_id	= $query->row('channel_id') ;
		$this->title		= $query->row('title') ;

		$this->parse_channel($this->channel_id);

		/** ---------------------------------------
		/**  Parse Channel Meta-Information
		/** ---------------------------------------*/

		// using entities because of <title> conversion by xss_clean()
		if (preg_match('/&lt;title&gt;(.+?)&lt;\/title&gt;/is', $parameters['4'], $matches))
		{
			// Load the text helper
			$this->EE->load->helper('text');

			$this->title = ($this->EE->config->item('auto_convert_high_ascii') == 'y') ? ascii_to_entities(trim($matches['1'])) : $matches['1'];
			$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
		}

		if (preg_match('/<category>(.*?)<\/category>/is', $parameters['4'], $matches))
		{
			$this->categories = trim($matches['1']);
			$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);

			if ($this->categories != '')
			{
				$this->check_categories($this->channel_id, '1');
			}
		}

		if (preg_match('/<sticky>(.+?)<\/sticky>/is', $parameters['4'], $matches))
		{
			$sticky = (trim($matches['1']) == 'yes' OR trim($matches['1']) == 'y') ? 'y' : 'n';
			$parameters['4'] = str_replace($matches['0'], '', $parameters['4']);
		}


		 /** ---------------------------------
		/**  Build our query string
		/** ---------------------------------*/

		$metadata = array(
							'entry_id'			=> $entry_id,
							'title'				=> $this->title,
							'ip_address'		=> $this->EE->input->ip_address(),
							'sticky'			=> $sticky,
							'status'			=> $this->status
						  );

		/** ---------------------------------------
		/**  Parse Channel Field Data
		/** ---------------------------------------*/

		$entrydata = array('channel_id' => $this->channel_id);

		if (count($this->fields) > 0)
		{
			foreach($this->fields as $field_id => $afield)
			{
				if ($this->block_entry === TRUE)
				{
					// Empty all fields.  Default field will be set with all
					// content.

					$entry_data['field_id_'.$field_id] = '';
					$entry_data['field_ft_'.$field_id] = $afield['1'];
				}
				elseif (preg_match('/<'.$afield['0'].'>(.*?)<\/'.$afield['0'].'>/is', $parameters['4'], $matches))
				{
					if ( ! isset($entry_data['field_id_'.$field_id]))
					{
						$entry_data['field_id_'.$field_id] = $matches['1'];
						$entry_data['field_ft_'.$field_id] = $afield['1'];
					}
					else
					{
						$entry_data['field_id_'.$field_id] .= "\n". $matches['1'];
					}

					$parameters['4'] = trim(str_replace($matches['0'], '', $parameters['4']));
				}
			}
		}

		// Default Field for Remaining Content

		if (trim($parameters['4']) != '' && count($this->fields) > 0)
		{
			if ( ! isset($entry_data[$this->field]))
			{
				$entry_data['field_id_'.$this->field] = trim($parameters['4']);
				$entry_data['field_ft_'.$this->field] = $this->fields[$this->field]['1'];
			}
			else
			{
				$entry_data[$this->field] .= ($this->block_entry === TRUE) ? trim($parameters['4']) : "\n".trim($parameters['4']);
			}
		}

		/** ---------------------------------
		/**  Update the entry data
		/** ---------------------------------*/

		$this->EE->db->where('entry_id', $entry_id);
		$this->EE->db->update('channel_titles', $metadata);

		$this->EE->db->where('entry_id', $entry_id);
		$this->EE->db->update('channel_data', $entry_data);

		/** ---------------------------------
		/**  Insert Categories, if any
		/** ---------------------------------*/

		if (count($this->ecategories) > 0)
		{
			$this->EE->db->where('entry_id', $entry_id);
			$this->EE->db->delete('category_posts');

			foreach($this->ecategories as $cat_id => $cat_name)
			{
				$data = array(
					'entry_id' => $entry_id,
					'cat_id' => $cat_id
				);

				$this->EE->db->insert('category_posts', $data);
			}
		}

		/** ---------------------------------
		/**  Clear caches if needed
		/** ---------------------------------*/

		if ($this->EE->config->item('new_posts_clear_caches') == 'y')
		{
			$this->EE->functions->clear_caching('all');
		}
		else
		{
			$this->EE->functions->clear_caching('sql');
		}

		/** ---------------------------------
		/**  Return Boolean TRUE
		/** ---------------------------------*/

		return $this->EE->xmlrpc->send_response(array(1,'boolean'));
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch member data
	 *
	 * @access	public
	 * @param	parameter list
	 * @return	void
	 */

	function fetch_member_data($username, $password)
	{
		// Query DB for member data.  Depending on the validation type we'll
		// either use the cookie data or the member ID gathered with the session query.

		$this->EE->db->select('exp_members.screen_name, exp_members.member_id, exp_members.email, exp_members.url, exp_members.group_id, exp_member_groups.*');
		$this->EE->db->where('exp_members.group_id = exp_member_groups.group_id');

		$conditions = array(
			'username' => $username,
			'password' => $this->EE->functions->hash(stripslashes($password))
		);

		$this->EE->db->where($conditions);
		$this->EE->db->from('members');
		$this->EE->db->from('member_groups');
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			$this->EE->db->select('exp_members.screen_name, exp_members.member_id, exp_members.email, exp_members.url, exp_members.group_id, exp_member_groups.*');
			$this->EE->db->where('exp_members.group_id = exp_member_groups.group_id');

			$conditions = array(
				'username' => $username,
				'password' => md5(stripslashes($password))
			);

			$this->EE->db->where($conditions);
			$this->EE->db->from('members');
			$this->EE->db->from('member_groups');
			$query = $this->EE->db->get();

			if ($query->num_rows() == 0)
			{
				return FALSE;
			}
		}

		// Turn the query rows into array values

		foreach ($query->row_array() as $key => $val)
		{
			$this->userdata[$key] = $val;
		}

		/** -------------------------------------------------
		/**  Find Assigned Channels
		/** -------------------------------------------------*/

		$assigned_channels = array();

		if ($this->userdata['group_id'] == 1)
		{
			$this->EE->db->select('channel_id');
			$result = $this->EE->db->get('channels');
		}
		else
		{
			$this->EE->db->select('channel_id');
			$result = $this->EE->db->get_where('channel_member_groups', array('group_id' => $this->userdata['group_id']));
		}

		if ($result->num_rows() > 0)
		{
			foreach ($result->result_array() as $row)
			{
				$assigned_channels[] = $row['channel_id'];
			}
		}
		else
		{
			return FALSE; // Nowhere to Post!!
		}

		$this->userdata['assigned_channels'] = $assigned_channels;


//$this->EE->session->userdata = $this->userdata;

		$this->EE->session->userdata = array_merge(
			$this->EE->session->userdata,
			array(
				'group_id'			=> $this->userdata['group_id'],
				'member_id'			=> $this->userdata['member_id'],
				'assigned_channels'	=> $this->userdata['assigned_channels']
			)
		);

		return TRUE;
	}




	/** -----------------------------------------
	/**  USAGE: Parse Out Channel Parameter Received
	/** -----------------------------------------*/


	function parse_channel($channel_id)
	{
		$this->EE->load->library('xmlrpc');

		/*
		Now channel id can come in many forms:
		1 - Basic channel id
		1|3 - Multiple channel ids
		1:5|8|9 - channel id with category(ies) id(s) specified
		1:5|8|9:open - channel id : categories : status
		*/

		$x					= explode(':',trim($channel_id));
		$this->categories	= ( ! isset($x['1'])) ? '' : trim($x['1']);
		$this->status		= ( ! isset($x['2'])) ? 'open' : trim($x['2']);

		$this->EE->db->select('channel_id, site_id');
		$this->EE->functions->ar_andor_string($x['0'], 'exp_channels.channel_id');
		$query = $this->EE->db->get('channels');

		if ($query->num_rows() == 0)
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_channel'));
		}

		$this->channel_id = $query->row('channel_id');
		$this->site_id	 = $query->row('site_id');

		if ($this->site_id != $this->EE->config->item('site_id'))
		{
			$this->EE->config->site_prefs('', $this->site_id);
		}

		foreach ($query->result_array() as $row)
		{
			if ( ! in_array($row['channel_id'], $this->userdata['assigned_channels']))
			{
				return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_channel'));
			}
		}

		/** ---------------------------------------
		/**  Check Categories
		/** ---------------------------------------*/

		if ($this->categories != '' && $this->categories != 'none')
		{
			$this->check_categories($this->channel_id);
		}

		/** ---------------------------------------
		/**  Find Fields
		/** ---------------------------------------*/

		$this->EE->db->select('field_name, field_id, field_type, field_fmt');
		$this->EE->db->from('channel_fields');
		$this->EE->db->from('channels');
		$this->EE->db->where('exp_channels.field_group = exp_channel_fields.group_id');
		$this->EE->functions->ar_andor_string($x['0'], 'exp_channels.channel_id');
		// AR: Probably need to deal with this differently in an ideal world
		$this->EE->db->where("(exp_channel_fields.field_type = 'textarea' OR exp_channel_fields.field_type = 'text')");
		$query = $this->EE->db->get();

		foreach($query->result_array() as $row)
		{
			// Default field
			// We try to make it $this->field_name if available otherwise we just use the
			// first textarea found.

			if (($this->field == '' OR $row['field_name'] == $this->field_name) && $row['field_type'] == 'textarea')
			{
				$this->field = $row['field_id'];
			}

			$this->fields[$row['field_id']] = array($row['field_name'], $row['field_fmt']);
		}

		// Configuation's Field ID trumps all, but only if it is set and found
		// in the fields for the specified channel

		if ($this->field_id != '' && in_array($this->field_id, $this->fields))
		{
			$this->field = $this->field_id;
		}
	}




	/** -----------------------------------------
	/**  USAGE: Check Validity of Categories
	/** -----------------------------------------*/

	function check_categories($channel_id, $debug = '0')
	{
		$this->EE->load->library('xmlrpc');

		$this->ecategories = array_unique(explode('|', $this->categories));

		$this->EE->db->select('exp_categories.cat_id, exp_categories.cat_name, exp_categories.parent_id');
		$this->EE->db->where('exp_categories.group_id = exp_channels.cat_group');
		$this->EE->db->where('exp_channels.channel_id', $channel_id);
		$this->EE->db->from('categories');
		$this->EE->db->from('channels');
		$query = $this->EE->db->get();

		if ($query->num_rows() == 0)
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_categories'));
		}

		$good		= 0;
		$all_cats	= array();

		foreach($query->result_array() as $row)
		{
			$all_cats[$row['cat_id']] = $row['cat_name'];

			if (in_array($row['cat_id'], $this->ecategories) OR in_array($row['cat_name'], $this->ecategories))
			{
				$good++;
				$cat_names[$row['cat_id']] = $row['cat_name'];

				if ($this->assign_parents == TRUE && $row['parent_id'] != '0')
				{
					$this->cat_parents[$row['parent_id']] = 'Parent';
				}
			}
		}

		if ($good < count($this->ecategories))
		{
			return $this->EE->xmlrpc->send_error_message('802', $this->EE->lang->line('invalid_categories'));
		}
		else
		{
			$this->ecategories = $cat_names;

			if ($this->assign_parents == TRUE && count($this->cat_parents) > 0)
			{
				foreach($this->cat_parents as $kitty => $galore)
				{
					$this->ecategories[$kitty] = $all_cats[$kitty];
				}
			}
		}
	}




	/** -----------------------------------------
	/**  USAGE: Link to Auto Discovery XML
	/** -----------------------------------------*/

	function edit_uri()
	{
		if ($action_id = $this->EE->functions->fetch_action_id('Blogger_api', 'edit_uri_output'))
		{
			$link	= $this->EE->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id;

			$link .= ( ! isset($this->EE->TMPL) OR ! $this->EE->TMPL->fetch_param('channel_id')) ? '' : '&channel_id='.urlencode($this->EE->TMPL->fetch_param('channel_id'));
			$link .= ( ! isset($this->EE->TMPL) OR ! $this->EE->TMPL->fetch_param('config_id')) ? '' : '&config_id='.urlencode($this->EE->TMPL->fetch_param('config_id'));

			$this->return_data = '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="'.$link.'" />';
		}

		return $this->return_data;
	}





	/** -----------------------------------------
	/**  USAGE: Auto-discovery XML
	/** -----------------------------------------*/

	function edit_uri_output()
	{
		$output = <<<EOT
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
	<service>
	  <engineName>ExpressionEngine</engineName>
	  <engineLink>http://expressionengine.com</engineLink>
	  <homePageLink>{homepage}</homePageLink>
	  <apis>
		 <api name="Blogger" preferred="true" apiLink="{api_link}" blogID="{channel_id}" />
	  </apis>
	</service>
</rsd>
EOT;

		$channel_id = ( ! isset($this->EE->TMPL) OR ! $this->EE->TMPL->fetch_param('channel_id')) ? '1' : $this->EE->TMPL->fetch_param('channel_id');

		// URL Override
		$channel_id = ( ! isset($_GET['channel_id'])) ? $channel_id : urldecode($_GET['channel_id']);

		$site_index	= $this->EE->functions->fetch_site_index(0, 0);
		$api_link	= $site_index.QUERY_MARKER.'ACT='.$this->EE->functions->fetch_action_id('Blogger_api', 'incoming');

		$api_link .= ( ! isset($_GET['config_id'])) ? '' : '&id='.urldecode($_GET['config_id']);

		$output = '<?xml version="1.0"?'.'>'.$this->LB.trim($output);
		$output = str_replace('{api_link}', $api_link, $output);
		$output = str_replace('{homepage}', $site_index, $output);
		$output = str_replace('{channel_id}', $channel_id, $output);

		@header("Content-Type: text/xml");
		exit($output);

	}


}


/* End of file mod.blogger_api.php */
/* Location: ./system/expressionengine/modules/blogger_api/mod.blogger_api.php */