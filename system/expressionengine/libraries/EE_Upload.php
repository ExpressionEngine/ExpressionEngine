<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
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
 * @author		EllisLab Dev Team
 * @link		http://expressionengine.com
 */
class EE_Upload extends CI_Upload 
{
	protected $use_temp_dir = FALSE;

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

	// --------------------------------------------------------------------

	/**
	 * Validate Upload Path
	 *
	 * Verifies that it is a valid upload path with proper permissions.
	 *
	 * @access	public
	 */
	public function validate_upload_path()
	{
		if ($this->use_temp_dir)
		{
			$path = $this->_discover_temp_path();

			if ($path)
			{
				$this->upload_path = $path;
			}
			else
			{
				$this->set_error('No usable temp directory found.');
				return FALSE;
			}
		}

		return parent::validate_upload_path();
	}

	// --------------------------------------------------------------------

	/**
	 * Keep the file in the temp directory?
	 *
	 * @access	public
	 */
	public function initialize($config = array())
	{
		if (isset($config['use_temp_dir']) && $config['use_temp_dir'] === TRUE)
		{
			$this->use_temp_dir = TRUE;
		}
		else
		{
			$this->use_temp_dir = FALSE;
		}

		parent::initialize($config);
	}

	// --------------------------------------------------------------------

	/**
	 * Find a valid temp directory?
	 *
	 * @access	public
	 */
	public function _discover_temp_path()
	{
		$attempt = array();
        $ini_path = ini_get('upload_tmp_dir');

        if ($ini_path)
        {
            $attempt[] = realpath($ini_path);
        }

		$attempt[] = sys_get_temp_dir();
		$attempt[] = @getenv('TMP');
		$attempt[] = @getenv('TMPDIR');
		$attempt[] = @getenv('TEMP');

		$valid_temps = array_filter($attempt);	// remove false's

		foreach ($valid_temps as $dir)
		{
			if (is_readable($dir) && is_writable($dir))
			{
				return $dir;
			}
		}

		return FALSE;
	}
}
// END CLASS

/* End of file EE_Upload.php */
/* Location: ./system/expressionengine/libraries/EE_Upload.php */
