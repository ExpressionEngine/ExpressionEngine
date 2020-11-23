<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_2_1_1;

/**
 * Update
 */
class Updater {

	var $version_suffix = '';

	function do_update()
	{
		// update channel_data table changing text fields to NOT NULL

		// Get all the text fields in the table
		$fields = ee()->db->field_data('channel_data');

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
				ee()->db->query("UPDATE `exp_channel_data` SET {$row['0']} = '' WHERE {$row['0']} IS NULL");
			}
		}

		// There was a brief time where this was altered but installer still set to 50 characters
		// so we update again to catch any from that window
		$fields = array(
			'email' => array(
					'name'			=> 'email',
					'type'			=> 'varchar',
					'constraint'	=> 72,
					'null'			=> FALSE
				)
			);

		ee()->smartforge->modify_column('members', $fields);

		// If 'comments_opened_notification' isn't already in exp_specialty_templates, add it.
		$values = array(
			'template_name'	=> 'comments_opened_notification',
			'data_title'	=> 'New comments have been added',
			'template_data'	=> addslashes($this->comments_opened_notification()),
		);

		$unique = array(
			'template_name'	=> 'comments_opened_notification'
		);

		ee()->smartforge->insert_set('specialty_templates', $values, $unique);

		// Do we need to move comment notifications?
		// We should skip it if the Comments module isn't installed.
		if ( ! ee()->db->table_exists('comments'))
		{
			return TRUE;
		}

		ee()->load->library('progress');
		ee()->progress->update_state("Creating Comment Subscription Table");

			$fields = array(
				'subscription_id'	=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
				'entry_id'			=> array('type' => 'int'	, 'constraint' => '10', 'unsigned' => TRUE),
				'member_id'			=> array('type' => 'int'	, 'constraint' => '10', 'default' => 0),
				'email'				=> array('type' => 'varchar', 'constraint' => '50'),
				'subscription_date'	=> array('type' => 'varchar', 'constraint' => '10'),
				'notification_sent'	=> array('type' => 'char'	, 'constraint' => '1', 'default' => 'n'),
				'hash'				=> array('type' => 'varchar', 'constraint' => '15')
			);

		ee()->load->dbforge();
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('subscription_id', TRUE);
		ee()->dbforge->add_key(array('entry_id', 'member_id'));
		ee()->smartforge->create_table('comment_subscriptions');


		// this step can be a doozy.  Set time limit to infinity.
		// Server process timeouts are out of our control, unfortunately
		@set_time_limit(0);
		ee()->db->save_queries = FALSE;

		ee()->progress->update_state('Moving Comment Notifications to Subscriptions');

		$batch = 50;
		$offset = 0;
		$progress   = "Moving Comment Notifications: %s";

		// If the notify field doesn't exist anymore, we can move on
		// to the next update file.
		if ( ! ee()->db->field_exists('notify', 'comments'))
		{
		   return TRUE;
		}

		ee()->db->distinct();
		ee()->db->select('entry_id, email, name, author_id');
		ee()->db->where('notify', 'y');

		$total = ee()->db->count_all_results('comments');

		if (count($total) > 0)
		{
			for ($i = 0; $i < $total; $i = $i + $batch)
			{
				ee()->progress->update_state(str_replace('%s', "{$offset} of {$count} queries", $progress));

				$data = array();

				ee()->db->distinct();
				ee()->db->select('entry_id, email, name, author_id');
				ee()->db->where('notify', 'y');
				ee()->db->limit($batch, $offset);
				$comment_data = ee()->db->get('comments');

				$s_date = NULL;

				// convert to comments
				foreach($comment_data->result_array() as $row)
				{
					$author_id = $row['author_id'];
					$rand = $author_id.$this->random('alnum', 8);
					$email = ($row['email'] == '') ? NULL : $row['email'];

					$data[] = array(
						'entry_id'			=> $row['entry_id'],
						'member_id'			=> $author_id,
						'email'				=> $email,
						'subscription_date'	=> $s_date,
						'notification_sent'	=> 'n',
						'hash'				=> $rand
					);
				}

				if (count($data) > 0)
				{
					if (ee()->db->insert_batch('comment_subscriptions', $data))
					{
						// Remove the notify flag from comment in the
						// comments table so that it won't be converted again
						// in case the comment subscription conversion doesn't
						// complete and has to be run again.
						ee()->db->set('notify', '');
						ee()->db->where('entry_id', $row['entry_id']);
						ee()->db->where('email', $row['email']);
						ee()->db->where('name', $row['name']);
						ee()->db->where('author_id', $row['author_id']);
						ee()->db->update('comments');
					}
				}

				$offset = $offset + $batch;
			}
		}

		//  Lastly- we get rid of the notify field
		ee()->smartforge->drop_column('comments', 'notify');

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

	function random($type = 'encrypt', $len = 8)
	{
		ee()->load->helper('string');
		return random_string($type, $len);
	}

}
/* END CLASS */

// EOF
