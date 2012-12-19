<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
 * @since       Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

    function Updater()
    {
        $this->EE =& get_instance();
		$this->EE->load->library('progress');

    }

    function do_update()
    {
		// update channel_data table changing text fields to NOT NULL
		
		// Get all the text fields in the table
		$fields = $this->EE->db->field_data('channel_data');
		$Q = array();
		
		$fields_to_alter = array();

		foreach ($fields as $field)
		{
			if (strncmp($field->name, 'field_id_', 9) == 0 && ($field->type == 'text' OR $field->type == 'blob'))
			{
				$fields_to_alter[] = array($field->name, $field->type);
			}
		} 		

 		if (count($fields_to_alter) > 0)
        {
			foreach ($fields_to_alter as $row)
            {
 				// We'll switch null values to empty string for our text fields
          		$Q[] = "UPDATE `exp_channel_data` SET {$row['0']} = '' WHERE {$row['0']} IS NULL";				
        	}
		}
		
		// There was a brief time where this was altered but installer still set to 50 characters
		// so we update again to catch any from that window
		$Q[] = "ALTER TABLE `exp_members` CHANGE `email` `email` varchar(72) NOT NULL";		

		
		$Q[] = "INSERT INTO exp_specialty_templates (template_name, data_title, template_data) values ('comments_opened_notification', 'New comments have been added', '".addslashes($this->comments_opened_notification())."')";

		$count = count($Q);
		
		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}
		
		// Do we need to move comment notifications?
		if ( ! $this->EE->db->table_exists('exp_comments'))
		{
        	return TRUE;			
		}		
		
		$this->EE->progress->update_state("Creating Comment Subscription Table");
			
			$fields = array(
				'subscription_id'	=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
				'entry_id'			=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE),
				'member_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
				'email'				=> array('type' => 'varchar', 'constraint' => '50'),
				'subscription_date'	=> array('type' => 'varchar', 'constraint' => '10'),
				'notification_sent'	=> array('type' => 'char'	, 'constraint' => '1', 'default' => 'n'),
				'hash'				=> array('type' => 'varchar', 'constraint' => '15')
			);

		$this->EE->load->dbforge();
		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('subscription_id', TRUE);
		$this->EE->dbforge->add_key(array('entry_id', 'member_id'));
		$this->EE->dbforge->create_table('comment_subscriptions');		


		// this step can be a doozy.  Set time limit to infinity.
        // Server process timeouts are out of our control, unfortunately
        @set_time_limit(0);
        $this->EE->db->save_queries = FALSE;
        
        $this->EE->progress->update_state('Moving Comment Notifications to Subscriptions');
                
        $batch = 50;
		$offset = 0;
		$progress   = "Moving Comment Notifications: %s";
		
		$this->EE->db->distinct();
		$this->EE->db->select('entry_id, email, name, author_id');
		$this->EE->db->where('notify', 'y');
		
		$total = $this->EE->db->count_all_results('comments');

		if (count($total) > 0)
		{
			for ($i = 0; $i < $total; $i = $i + $batch)
            {
            	$this->EE->progress->update_state(str_replace('%s', "{$offset} of {$count} queries", $progress));	

				$data = array();
		
				$this->EE->db->distinct();
				$this->EE->db->select('entry_id, email, name, author_id');
				$this->EE->db->where('notify', 'y');
				$this->EE->db->limit($batch, $offset);
				$comment_data = $this->EE->db->get('comments');
							
				$s_date = NULL;
					
				// convert to comments
            	foreach($comment_data->result_array() as $row)
            	{
					$author_id = $row['author_id'];
					$rand = $author_id.$this->random('alnum', 8);
					$email = ($row['email'] == '') ? NULL : $row['email'];
			
               		$data[] = array(
                    		'entry_id'       		=> $row['entry_id'],
                    		'member_id'     		=> $author_id,
                    		'email'        			=> $email,
                    		'subscription_date'		=> $s_date,
                    		'notification_sent'     => 'n',
                    		'hash'           		=> $rand
                			);
				}
				
				if (count($data) > 0)
				{
					$this->EE->db->insert_batch('comment_subscriptions', $data);
				}
				
				$offset = $offset + $batch; 
			}
		}
		
	
		//  Lastly- we get rid of the notify field
		$this->EE->db->query("ALTER TABLE `exp_comments` DROP COLUMN `notify`");
		
		return TRUE;
	}

    // ------------------------------------------------------------------------


	function comments_opened_notification()
	{
return <<<EOF

Responses have been added to the entry you subscribed to at:
{channel_name}

The title of the entry is:
{entry_title}

You can see the comments at the following URL:
{comment_url}

{comments}
{comment} 
{/comments}

To stop receiving notifications for this entry, click here:
{notification_removal_url}
EOF;
	}
	
    // ------------------------------------------------------------------------

	function random($type = 'encrypt', $len = 8)
	{
		$this->EE->load->helper('string');
		return random_string($type, $len);
	}	
	
}   
/* END CLASS */

/* End of file ud_211.php */
/* Location: ./system/expressionengine/installer/updates/ud_211.php */