<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/api/Api_channel_entries.php';

class Api_sc_channel_entries extends Api_channel_entries
{
	/**
	 * Why? because I want to preserve fields that haven't been POSTed
	 *
	 **/
	public function _prepare_data(&$data, &$mod_data)
	{
		$this->_pre_prepare_data($data);
		parent::_prepare_data($data, $mod_data);
		$this->_post_prepare_data($data);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Pre Prepare Data
	 */
	public function _pre_prepare_data(&$data)
	{
		if ( ! $this->EE->safecracker->edit)
		{
			return;
		}
		
		foreach($this->EE->safecracker->custom_fields as $field)
		{
			if (empty($field['isset']))
			{
				//we put in a dummy value earlier, so _check_data_for_errors
				//wouldn't fail.
				//remove it from data so save() doesn't get called
				unset($data['field_id_'.$field['field_id']]);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Post Prepare Data
	 */
	public function _post_prepare_data(&$data)
	{
		//remove unwanted keys
		foreach ($data as $key => $value)
		{
			$fields = $this->EE->db->list_fields('channel_data');
		
			if (preg_match('/^field_(id|ft|dt)/', $key) && ! in_array($key, $fields))
			{
				unset($data[$key]);
			}
		}
		
		if ( ! $this->EE->safecracker->edit)
		{
			return;
		}

		$rel_ids = array();
		$checkbox_fields = isset($data['checkbox_fields']) ? explode('|', $data['checkbox_fields']) : array();
		
		foreach($this->EE->safecracker->custom_fields as $field)
		{
			// Preserve off-screen checkboxes
			if ($field['field_type'] == 'checkboxes')
			{
				if ($this->EE->safecracker->preserve_checkboxes)
				{
					// If a checkbox field was present on screen but has no value,
					// assign a blank value to it so the database is updated
					if (in_array($field['field_name'], $checkbox_fields) AND
						! isset($data[$field['field_name']]))
					{
						$data[$field['field_name']] = '';
						continue;
					}
				}
				else
				{
					continue;
				}
			}
			
			// Preserve the original value if this field wasn't POSTed
			if (empty($field['isset']))
			{
				$data['field_id_'.$field['field_id']] = ($this->EE->safecracker->entry($field['field_name']) !== FALSE)
					? $this->EE->safecracker->entry($field['field_name']) : '';


				// The entry API expects the rel_child_id from the exp_relationships field 
				// rather than the rel_id stored in channel_data
				if ($field['field_type'] == 'rel' && $this->EE->safecracker->entry($field['field_name']) !== FALSE)
				{
					$rel_ids[$this->EE->safecracker->entry($field['field_name'])] = 'field_id_'.$field['field_id'];
				}

			}
		}

		if ( ! empty($rel_ids))
		{
			$relationships = $this->EE->safecracker->api_safe_rel_ids(array_keys($rel_ids));
			
			if ($relationships->num_rows() > 0)
			{
				foreach ($relationships->result_array() as $row)
				{
					 $data[$rel_ids[$row['rel_id']]] = $row['rel_child_id'];
					
				}
				
			}
		}
	}
}