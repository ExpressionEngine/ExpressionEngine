<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Js_tool_model extends CI_Model {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function Js_tool_model()
	{
		parent::CI_Model();	
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get files
	 *
	 * @access	public
	 */
	function get_checksums()
	{
		$query = $this->db->get('javascript_checksums');
		
		if ($query->num_rows() == 0)
		{
			return array();
		}
		
		$result = array();
		foreach($query->result() as $row)
		{
			$result[$row->filepath] = $row;
		}
		
		return $result;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Remove file(s)
	 *
	 * @access	public
	 */
	function remove_checksums($files = array())
	{
		if ( ! is_array($files))
		{
			$files = array($files);
		}
		
		if (count($files) == 0)
		{
			return;
		}
		
		$this->db->where_in('filepath', $files);
		$this->db->delete('javascript_checksums');
	}

	// --------------------------------------------------------------------

	/**
	 * Insert / Update Checksum
	 *
	 * @access	public
	 */
	function store_checksum($file, $checksum)
	{
		$this->db->where('filepath', $file);
		$query = $this->db->get('javascript_checksums');
		
		if ($query->num_rows() > 0)
		{
			$this->db->set('checksum', $checksum);
			$this->db->where('filepath', $file);
			$this->db->update('javascript_checksums');
		}
		else
		{
			$this->db->insert('javascript_checksums', array('filepath' => $file, 'checksum' => $checksum));
		}
	}

	// --------------------------------------------------------------------

}

// END Js_tool_model class


/* End of file js_tool_model.php */
/* Location: ./system/expressionengine/third_party/js_tool/models/js_tool_model.php */