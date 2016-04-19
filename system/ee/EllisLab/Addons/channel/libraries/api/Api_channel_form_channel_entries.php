<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/api/Api_channel_entries.php';

class Api_channel_form_channel_entries extends Api_channel_entries
{
	/**
	 * Why? because I want to preserve fields that haven't been POSTed
	 *
	 **/
	public function _prepare_data(&$data, &$mod_data, $autosave = FALSE)
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
		if ( ! ee()->channel_form->edit)
		{
			return;
		}

		foreach(ee()->channel_form->custom_fields as $field)
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
			$fields = ee()->db->list_fields('channel_data');

			if (preg_match('/^field_(id|ft|dt)/', $key) && ! in_array($key, $fields))
			{
				unset($data[$key]);
			}
		}

		if ( ! ee()->channel_form->edit)
		{
			return;
		}

		$checkbox_fields = isset($data['checkbox_fields']) ? explode('|', $data['checkbox_fields']) : array();

		foreach(ee()->channel_form->custom_fields as $field)
		{
			// Preserve off-screen checkboxes
			if ($field['field_type'] == 'checkboxes')
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

			// Preserve the original value if this field wasn't POSTed
			if (empty($field['isset']))
			{
				$data['field_id_'.$field['field_id']] = (ee()->channel_form->entry($field['field_name']) !== FALSE)
					? ee()->channel_form->entry($field['field_name']) : '';
			}
		}
	}
}
