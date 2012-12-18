<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Upload Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Upload extends CI_Upload 
{
	/**
	 * Constructor
	 */ 
	function __construct($props = array())
	{
		parent::__construct();

		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		$this->EE->load->helper('xss');
		
		$props['xss_clean'] = xss_check();

		$this->initialize($props);

		log_message('debug', "Upload Class Initialized");
	}	

	// --------------------------------------------------------------------

	/**
	 * Overwrite OR Rename Files Manually
	 *
	 * @access	public
	 * @param string $original_files Path to the original file
	 * @param string $new The new file name
	 * @param boolean $type_match Should we make sure the extensions match?
	 * @return boolean TRUE if it was renamed properly, FALSE otherwise
	 */
   function file_overwrite($original_file = '', $new = '', $type_match = TRUE)
	{
		$this->file_name = $new;
		
		// If renaming a file, it should have same file type suffix as the original
		if ($type_match === TRUE)
		{
			if (sizeof(explode('.', $this->file_name)) == 1 OR (array_pop(explode('.', $this->file_name)) != array_pop(explode('.', $original_file))))
			{
				$this->set_error('invalid_filetype');
				return FALSE;
			}
		}
		
		if ($this->remove_spaces == 1)
		{
			$this->file_name = preg_replace("/\s+/", "_", $this->file_name);
			$original_file = preg_replace("/\s+/", "_", $original_file);
		}
		
		// Check to make sure the file doesn't already exist
		if (file_exists($this->upload_path . $this->file_name))
		{
			$this->set_error('file_exists');
			return FALSE;
		}
 		
		if ( ! @copy($this->upload_path.$original_file, $this->upload_path.$this->file_name))
		{
			$this->set_error('copy_error');
			return FALSE;
		}
		
		unlink ($this->upload_path.$original_file);

		return TRUE;
	}
}
// END CLASS

/* End of file EE_Upload.php */
/* Location: ./system/expressionengine/libraries/EE_Upload.php */
