<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
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
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class EE_Upload extends CI_Upload {


	/**
	 * Constructor
	 */	
	function __construct($props = array())
	{
		parent::__construct();


		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		if (count($props) > 0)
		{
			$this->initialize($props);
		}

		log_message('debug', "Upload Class Initialized");
		
	}	


	// --------------------------------------------------------------------

	/**
	 * Set the file name
	 *
	 * This function takes a filename/path as input and looks for the
	 * existence of a file with the same name. If found, we put a temp prefix on it and append a
	 * number to the end of the filename to avoid overwriting a pre-existing file.
	 *
	 * @param	string
	 * @param	string
	 * @return	string
	 */


	public function set_filename($path, $filename)
	{
		if ($this->encrypt_name == TRUE)
		{
			mt_srand();
			$filename = md5(uniqid(mt_rand())).$this->file_ext;
		}

		if ( ! file_exists($path.$filename))
		{
			return $filename;
		}

		$filename = str_replace($this->file_ext, '', $filename);

		$new_filename = '';
		for ($i = 1; $i < 100; $i++)
		{
			if ( ! file_exists($path.$this->temp_prefix.$filename.$i.$this->file_ext))
			{
				$new_filename = $this->temp_prefix.$filename.$i.$this->file_ext;
				break;
			}
		}

		if ($new_filename == '')
		{
			$this->set_error('upload_bad_filename');
			return FALSE;
		}

		return $new_filename;
	}


	// --------------------------------------------------------------------

	/**
	 * Overwrite OR Rename Files Manually
	 *
	 * @access	public
	 * @return	void
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
 
        if ( ! @copy($this->upload_path.$this->temp_prefix.$original_file, $this->upload_path.$this->file_name))
		{
			$this->set_error('copy_error');
			return FALSE;
        }			
        
		unlink ($this->upload_path.$this->temp_prefix.$original_file);

		return TRUE;    		
    }
    /* END */



}
// END CLASS

/* End of file EE_Upload.php */
/* Location: ./system/expressionengine/libraries/EE_Upload.php */