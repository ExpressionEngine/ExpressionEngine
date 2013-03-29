<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Rel_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Relationship',
		'version'	=> '1.0'
	);
	

	function validate($data) { }
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Relationship Field
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function delete($ids)
	{
		ee()->db->where_in('rel_parent_id', $ids);
		ee()->db->delete('relationships');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Relationship Field
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function display_field($data)
	{
		if ($this->settings['field_related_orderby'] == 'date')
		{
			$this->settings['field_related_orderby'] = 'entry_date';
		}
		
		ee()->db->select('entry_id, title');
		ee()->db->where('channel_id', $this->settings['field_related_id']);
		ee()->db->order_by($this->settings['field_related_orderby'], $this->settings['field_related_sort']);
		
		if ($this->settings['field_related_max'] > 0)
		{
			ee()->db->limit($this->settings['field_related_max']);
		}
		
		$relquery = ee()->db->get('channel_titles');

		if ($relquery->num_rows() == 0)
		{
			return ee()->lang->line('no_related_entries');
		}
		else
		{
			if ( ! isset($_POST[$this->field_name]))
			{
				ee()->db->select('rel_child_id');
				$relentry = ee()->db->get_where('relationships', array('rel_id' => $data));

				if ($relentry->num_rows() == 1)
				{
					$data = $relentry->row('rel_child_id') ;
				}
			}

			$field_options[''] = '--';

			foreach ($relquery->result_array() as $relrow)
			{
				$field_options[$relrow['entry_id']] = $relrow['title'];
			}

			return form_dropdown($this->field_name, $field_options, $data, 'id="field_id_'.$this->field_id.'"');
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Display Relationship Field Settings
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	function display_settings($data)
	{
		// Channel Relationships
		// Fetch the channel names

		$sql = "SELECT channel_id, channel_title, site_label FROM exp_channels, exp_sites
				WHERE exp_channels.site_id = exp_sites.site_id ";

		if (ee()->config->item('multiple_sites_enabled') !== 'y')
		{
			$sql .= "AND exp_channels.site_id = '1' ";
		}

		$query = ee()->db->query($sql."ORDER BY channel_title asc");


		$field_related_channel_id_options = array();

		foreach ($query->result_array() as $row)
		{
			$field_related_channel_id_options[$row['channel_id']] = (ee()->config->item('multiple_sites_enabled') == 'y') ? $row['site_label'].NBS.'-'.NBS.$row['channel_title'] : $row['channel_title'];
		}

		$field_related_orderby_options = array(
			'title'	=> ee()->lang->line('orderby_title'),
			'date'	=> ee()->lang->line('orderby_date')
		);

		$field_related_sort_options = array(
			'desc'	=> ee()->lang->line('sort_desc'),
			'asc'	=> ee()->lang->line('sort_asc')
		);

		$field_related_max_options = array(
			'0' 	=> ee()->lang->line('all'),
			'25' 	=> 25,
			'50' 	=> 50,
			'100' 	=> 100,
			'250' 	=> 250,
			'500' 	=> 500,
			'1000' 	=> 1000
		);

		ee()->table->add_row(
			lang('select_related_channel', 'field_related_channel_id'),
			form_dropdown('field_related_channel_id', $field_related_channel_id_options, $data['field_related_id'], 'id="field_related_channel_id"')
		);
		
		ee()->table->add_row(
			'<strong>'.lang('display_criteria').'</strong>',
			form_dropdown('field_related_orderby', $field_related_orderby_options, $data['field_related_orderby'], 'id="field_related_orderby"').NBS.
					lang('in').NBS.form_dropdown('field_related_sort', $field_related_sort_options, $data['field_related_sort'], 'id="field_related_sort"').NBS.
					lang('limit').NBS.form_dropdown('field_related_max', $field_related_max_options, $data['field_related_max'], 'id="field_related_max"')
		);
	}
	
	
	function save_settings($data)
	{
		// Date or relationship types don't need formatting.
		$data['field_fmt'] = 'none';
		$data['field_show_fmt'] = 'n';
		$_POST['update_formatting'] = 'y';
		
		return $data;
	}	
	
	

	// --------------------------------------------------------------------
	
	function settings_modify_column($data)
	{
		if ($data['ee_action'] == 'delete')
		{
			ee()->db->select('field_id_'.$data['field_id']);
			ee()->db->where('field_id_'.$data['field_id'].' !=', '0');
			$rquery = ee()->db->get('channel_data');

			if ($rquery->num_rows() > 0)
			{
				$rel_ids = array();

				foreach ($rquery->result_array() as $row)
				{
					$rel_ids[] = $row['field_id_'.$data['field_id']];
				}

				ee()->db->where_in('rel_id', $rel_ids);
				ee()->db->delete('relationships');
			}
		}
	
		$fields['field_id_'.$data['field_id']] = array(
			'type' 			=> 'INT',
			'constraint'	=> 10,
			'default'		=> 0
			);	

		return $fields;
	}		
	
}

// END Rel_ft class

/* End of file ft.rel.php */
/* Location: ./system/expressionengine/fieldtypes/ft.rel.php */