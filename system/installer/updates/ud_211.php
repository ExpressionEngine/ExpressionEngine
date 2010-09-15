<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license     http://expressionengine.com/user_guide/license.html
 * @link        http://expressionengine.com
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
 * @author      ExpressionEngine Dev Team
 * @link        http://expressionengine.com
 */
class Updater {

	var $version_suffix = '';

    function Updater()
    {
        $this->EE =& get_instance();

        // Grab the config file
        if ( ! @include($this->EE->config->config_path))
        {
            show_error('Your config'.EXT.' file is unreadable. Please make sure the file exists and that the file permissions to 666 on the following file: expressionengine/config/config.php');
        }
        
        if (isset($conf))
        {
            $config = $conf;
        }
        
        // Does the config array exist?
        if ( ! isset($config) OR ! is_array($config))
        {
            show_error('Your config'.EXT.'file does not appear to contain any data.');
        }
        
        $this->EE->load->library('progress');
        
        $this->config =& $config;
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

		
		$Q[] = "INSERT INTO exp_specialty_templates(template_name, data_title, template_data) values ('comments_opened_notification', 'New comments have been added', '".addslashes(comments_opened_notification())."')";

		$count = count($Q);
		
		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}
		
		if ($this->db->table_exists('comment'))
		{
			// there's another step yet
        	return 'comment_notification_conversion';			
		}
		
		return TRUE;
	}
	
	function comment_notification_conversion()
	{
		// this step can be a doozy.  Set time limit to infinity.
        // Server process timeouts are out of our control, unfortunately
        @set_time_limit(0);
        $this->EE->db->save_queries = FALSE;
        
        $this->EE->progress->update_state('Moving Comment Notifications to Subscriptions');
                
        $batch = 500;
		$offset = 0;
		$progress   = "Moving Comment Notifications: %s";
		
		$this->EE->db->distinct();
		$this->EE->db->select('entry_id, email, name, author_id');
		$this->EE->db->where('notify', 'y');
		
		$count = $this->db->count_all_results('comments');

		if (count($count) > 0)
		{
			for ($i = 0; $i < $count; $i = $i + $batch)
            {
            	$this->EE->progress->update_state(str_replace('%s', "{$offset} of {$count} queries", $progress));	
		
				$this->EE->db->distinct();
				$this->EE->db->select('entry_id, email, name, author_id');
				$this->EE->db->where('notify', 'y');
				$this->EE->db->limit($offset, 500);
				$comment_data = $this->EE->db->get('comments');
							
				$s_date = $this->EE->localize->now;
					
				// convert to comments
            	foreach($comment_data->result_array() as $row)
            	{
					$author_id = $row['author_id'];
					$rand = $author_id.$this->EE->functions->random('alnum', 8);
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
				
				$this->EE->db->insert_batch('comment_subscriptions', $data);
				$offset = $offset + $batch; 
			}
		}
		
		//  Lastly- we get rid of the notify field
		$this->EE->db->query("ALTER TABLE `exp_comments` DROP COLUMN `notify`");
		
		return TRUE;
	}


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
	
	
}   
/* END CLASS */

/* End of file ud_211.php */
/* Location: ./system/expressionengine/installer/updates/ud_211.php */