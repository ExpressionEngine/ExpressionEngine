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
 * ExpressionEngine Comment Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Comment_mcp {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Comment_mcp()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Comment Notification
	 *
	 * @access	public
	 * @return	string
	 */
	function delete_comment_notification()
	{
		if ( ! $id = $this->EE->input->get_post('id'))
		{
			return FALSE;
		}

		if ( ! is_numeric($id))
		{
			return FALSE;
		}

		$this->EE->lang->loadfile('comment');

		$this->EE->db->select('entry_id, email');
		$query = $this->EE->db->get_where('comments', array('comment_id' => $id));

		if ($query->num_rows() != 1)
		{
			return FALSE;
		}

		if ($query->num_rows() == 1)
		{ 
			$this->EE->db->set('notify', 'n');

			$conditions = array(
				'entry_id' => $query->row('entry_id'),
				'email'	   => $query->row('email')
			);

			$this->EE->db->where($conditions);
			$this->EE->db->update('comments');
		}

		$data = array(	'title' 	=> $this->EE->lang->line('cmt_notification_removal'),
						'heading'	=> $this->EE->lang->line('thank_you'),
						'content'	=> $this->EE->lang->line('cmt_you_have_been_removed'),
						'redirect'	=> '',
						'link'		=> array($this->EE->config->item('site_url'), stripslashes($this->EE->config->item('site_name')))
					 );

		$this->EE->output->show_message($data);
	}
}
// END CLASS

/* End of file mcp.comment.php */
/* Location: ./system/expressionengine/modules/comment/mcp.comment.php */