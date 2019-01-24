<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Upload
 */
class EE_Upload {

	public $max_size				= 0;
	public $max_width				= 0;
	public $max_height				= 0;
	public $max_filename			= 0;
	public $allowed_types			= "";
	public $file_temp				= "";
	public $file_name				= "";
	public $orig_name				= "";
	public $file_type				= "";
	public $file_size				= "";
	public $file_ext				= "";
	public $upload_path				= "";
	public $overwrite				= FALSE;
	public $encrypt_name			= FALSE;
	public $is_image				= FALSE;
	public $image_width				= '';
	public $image_height			= '';
	public $image_type				= '';
	public $image_size_str			= '';
	public $error_msg				= array();
	public $mimes					= array();
	public $remove_spaces			= TRUE;
	public $xss_clean				= FALSE;
	public $temp_prefix				= "temp_file_";
	public $client_name				= '';

	protected $use_temp_dir			= FALSE;
	protected $raw_upload = FALSE;
	protected $_file_name_override	= '';
	protected $blacklisted_extensions = array();

	/**
	 * Constructor
	 */
	function __construct($props = array())
	{
		if (count($props) > 0)
		{
			$this->initialize($props);
		}

		ee()->load->helper('xss');

		$props['xss_clean'] = xss_check();

		$this->initialize($props);

		ee()->load->library('mime_type');
		log_message('debug', "Upload Class Initialized");

		$this->blacklisted_extensions = array(
			'php',
			'php3',
			'php4',
			'php5',
			'php7',
			'phps',
			'phtml'
		);
	}

	/**
	 * Take raw file data and populate our tmp directory and FILES array and
	 * then pass it through the normal do_upload routine.
	 *
	 * @access	public
	 * @param string $name The file name
	 * @param string $type The mime type
	 * @param string $data The raw file data
	 * @return mixed The result of do_upload
	 */
	public function raw_upload($name, $data)
	{
		// This will force do_upload to skip its is_uploaded_file checks
		$this->raw_upload = TRUE;

		$tmp = tempnam(sys_get_temp_dir(), 'raw');

		if (file_put_contents($tmp, $data) === FALSE)
		{
			throw new Exception('Could not upload file');
		}

		$_FILES['userfile'] = array(
			'name' => $name,
			'size' => mb_strlen($data),
			'tmp_name' => $tmp,
			'error' => UPLOAD_ERR_OK
		);

		return $this->do_upload();
	}

	/**
	 * Perform the file upload
	 *
	 * @return	bool
	 */
	public function do_upload($field = 'userfile')
	{
		// Is $_FILES[$field] set? If not, no reason to continue.
		if ( ! isset($_FILES[$field]))
		{
			$this->set_error('upload_no_file_selected');
			return FALSE;
		}

		// Is the upload path valid?
		if ( ! $this->validate_upload_path())
		{
			// errors will already be set by validate_upload_path() so just return FALSE
			return FALSE;
		}

		// Was the file able to be uploaded? If not, determine the reason why.
		if ( ! $this->raw_upload && ! is_uploaded_file($_FILES[$field]['tmp_name']))
		{
			$error = ( ! isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];

			switch($error)
			{
				case 1:	// UPLOAD_ERR_INI_SIZE
					$this->set_error('upload_file_exceeds_limit');
					break;
				case 2: // UPLOAD_ERR_FORM_SIZE
					$this->set_error('upload_file_exceeds_form_limit');
					break;
				case 3: // UPLOAD_ERR_PARTIAL
					$this->set_error('upload_file_partial');
					break;
				case 4: // UPLOAD_ERR_NO_FILE
					$this->set_error('upload_no_file_selected');
					break;
				case 6: // UPLOAD_ERR_NO_TMP_DIR
					$this->set_error('upload_no_temp_directory');
					break;
				case 7: // UPLOAD_ERR_CANT_WRITE
					$this->set_error('upload_unable_to_write_file');
					break;
				case 8: // UPLOAD_ERR_EXTENSION
					$this->set_error('upload_stopped_by_extension');
					break;
				default :   $this->set_error('upload_no_file_selected');
					break;
			}

			return FALSE;
		}

		// Set the uploaded data as class variables
		$this->file_temp = $_FILES[$field]['tmp_name'];
		$this->file_size = $_FILES[$field]['size'];
		$this->file_type = ee()->mime_type->ofFile($this->file_temp);
		$this->file_name = $this->_prep_filename($_FILES[$field]['name']);
		$this->file_ext	 = $this->get_extension($this->file_name);
		$this->client_name = $this->file_name;

		// Is this a hidden file? Not allowed
		if (strncmp($this->file_name, '.', 1) == 0)
		{
			$this->set_error('upload_invalid_file');
			return FALSE;
		}

		// Disallowed File Names
		$disallowed_names = ee()->config->item('upload_file_name_blacklist');

		if ($disallowed_names !== FALSE)
		{
			if ( ! is_array($disallowed_names))
			{
				$disallowed_names = array($disallowed_names);
			}
			$disallowed_names = array_map("strtolower", $disallowed_names);
		}
		else
		{
			$disallowed_names = array();
		}

		// Yes ".htaccess" is covered by the above hidden file check
		// but this is here as an extra sanity-saving precation.
		$disallowed_names[] = '.htaccess';
		$disallowed_names[] = 'web.config';

		if (in_array(strtolower($this->file_name), $disallowed_names))
		{
			$this->set_error('upload_invalid_file');
			return FALSE;
		}


		// Is the file type allowed to be uploaded?
		if ( ! $this->is_allowed_filetype())
		{
			$this->set_error('upload_invalid_file');
			return FALSE;
		}

		// if we're overriding, let's now make sure the new name and type is allowed
		if ($this->_file_name_override != '')
		{
			$this->file_name = $this->_prep_filename($this->_file_name_override);
			$this->file_ext  = $this->get_extension($this->file_name);

			if ( ! $this->is_allowed_filetype(TRUE))
			{
				$this->set_error('upload_invalid_file');
				return FALSE;
			}
		}

		// Convert the file size to kilobytes
		if ($this->file_size > 0)
		{
			$this->file_size = round($this->file_size/1024, 2);
		}

		// Is the file size within the allowed maximum?
		if ( ! $this->is_allowed_filesize())
		{
			$this->set_error('upload_invalid_filesize');
			return FALSE;
		}

		// Are the image dimensions within the allowed size?
		// Note: This can fail if the server has an open_basdir restriction.
		if ( ! $this->is_allowed_dimensions())
		{
			$this->set_error('upload_invalid_dimensions');
			return FALSE;
		}

		// Sanitize the file name for security
		$this->file_name = $this->clean_file_name($this->file_name);

		// Truncate the file name if it's too long
		if ($this->max_filename > 0)
		{
			$this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
		}

		// Remove white spaces in the name
		if ($this->remove_spaces == TRUE)
		{
			$this->file_name = preg_replace("/\s+/", "_", $this->file_name);
		}

		/*
		 * Validate the file name
		 * This function appends an number onto the end of
		 * the file if one with the same name already exists.
		 * If it returns false there was a problem.
		 */
		$this->orig_name = $this->file_name;

		if ($this->overwrite == FALSE)
		{
			$this->file_name = $this->set_filename($this->upload_path, $this->file_name);

			if ($this->file_name === FALSE)
			{
				return FALSE;
			}
		}

		/*
		 * Run the file through the XSS hacking filter
		 * This helps prevent malicious code from being
		 * embedded within a file.  Scripts can easily
		 * be disguised as images or other file types.
		 */
		if ($this->xss_clean)
		{
			if ($this->do_xss_clean() === FALSE)
			{
				$this->set_error('upload_unable_to_write_file');
				return FALSE;
			}
		}

		// If this is an image make sure it doesn't have PHP embedded in it
		if ($this->is_image)
		{
			if ($this->do_embedded_php_check() === FALSE)
			{
				$this->set_error('upload_unable_to_write_file');
				return FALSE;
			}
		}

		/*
		 * Move the file to the final destination
		 * To deal with different server configurations
		 * we'll attempt to use copy() first.  If that fails
		 * we'll use move_uploaded_file().  One of the two should
		 * reliably work in most environments
		 */
		if ( ! @copy($this->file_temp, $this->upload_path.$this->file_name))
		{
			if ( ! @move_uploaded_file($this->file_temp, $this->upload_path.$this->file_name))
			{
				$this->set_error('upload_destination_error');
				return FALSE;
			}
		}

		@chmod($this->upload_path.$this->file_name, FILE_WRITE_MODE);

		/*
		 * Set the finalized image dimensions
		 * This sets the image width/height (assuming the
		 * file was an image).  We use this information
		 * in the "data" function.
		 */
		$this->set_image_properties($this->upload_path.$this->file_name);

		return TRUE;
	}

	/**
	 * Finalized Data Array
	 *
	 * Returns an associative array containing all of the information
	 * related to the upload, allowing the developer easy access in one array.
	 *
	 * @return	array
	 */
	public function data()
	{
		return array (
						'file_name'			=> $this->file_name,
						'file_type'			=> $this->file_type,
						'file_path'			=> $this->upload_path,
						'full_path'			=> $this->upload_path.$this->file_name,
						'raw_name'			=> str_replace($this->file_ext, '', $this->file_name),
						'orig_name'			=> $this->orig_name,
						'client_name'		=> $this->client_name,
						'file_ext'			=> $this->file_ext,
						'file_size'			=> $this->file_size,
						'is_image'			=> $this->is_image(),
						'image_width'		=> $this->image_width,
						'image_height'		=> $this->image_height,
						'image_type'		=> $this->image_type,
						'image_size_str'	=> $this->image_size_str,
					);
	}

	/**
	 * Set Upload Path
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_upload_path($path)
	{
		// Make sure it has a trailing slash
		$this->upload_path = rtrim($path, '/').'/';
	}

	/**
	 * Set the file name
	 *
	 * This function takes a filename/path as input and looks for the
	 * existence of a file with the same name. If found, it will append a
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

		$new_filename = ee('Filesystem')->getUniqueFilename($path.$filename);
		$new_filename = str_replace($path, '', $new_filename);

		if ($new_filename == '')
		{
			$this->set_error('upload_bad_filename');
			return FALSE;
		}
		else
		{
			return $new_filename;
		}
	}

	/**
	 * Set Maximum File Size
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_filesize($n)
	{
		$this->max_size = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Maximum File Name Length
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_filename($n)
	{
		$this->max_filename = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Maximum Image Width
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_width($n)
	{
		$this->max_width = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Maximum Image Height
	 *
	 * @param	integer
	 * @return	void
	 */
	public function set_max_height($n)
	{
		$this->max_height = ((int) $n < 0) ? 0: (int) $n;
	}

	/**
	 * Set Allowed File Types
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_allowed_types($types)
	{
		if ( ! is_array($types) && $types == '*')
		{
			$this->allowed_types = '*';
			return;
		}
		$this->allowed_types = explode('|', $types);
	}

	/**
	 * Set Image Properties
	 *
	 * Uses GD to determine the width/height/type of image
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_image_properties($path = '')
	{
		if ( ! $this->is_image())
		{
			return;
		}

		if (function_exists('getimagesize'))
		{
			if (FALSE !== ($D = @getimagesize($path)))
			{
				$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');

				$this->image_width		= $D['0'];
				$this->image_height		= $D['1'];
				$this->image_type		= ( ! isset($types[$D['2']])) ? 'unknown' : $types[$D['2']];
				$this->image_size_str	= $D['3'];  // string containing height and width
			}
		}
	}

	/**
	 * Set XSS Clean
	 *
	 * Enables the XSS flag so that the file that was uploaded
	 * will be run through the XSS filter.
	 *
	 * @param	bool
	 * @return	void
	 */
	public function set_xss_clean($flag = FALSE)
	{
		$this->xss_clean = ($flag == TRUE) ? TRUE : FALSE;
	}

	/**
	 * Validate the image
	 *
	 * @return	bool
	 */
	public function is_image()
	{
		return ee()->mime_type->fileIsImage($this->file_temp);
	}

	/**
	 * Verify that the filetype is allowed
	 *
	 * @return	bool
	 */
	public function is_allowed_filetype($ignore_mime = FALSE)
	{
		$ext = strtolower(ltrim($this->file_ext, '.'));

		if (in_array($ext, $this->blacklisted_extensions))
		{
			return FALSE;
		}

		if ( ! empty($this->allowed_types) && is_array($this->allowed_types) && ! in_array($ext, $this->allowed_types))
		{
			return FALSE;
		}

		if ($ignore_mime === TRUE)
		{
			return TRUE;
		}

		if ($this->is_image)
		{
			return ee()->mime_type->fileIsImage($this->file_temp);
		}

		return ee()->mime_type->fileIsSafeForUpload($this->file_temp);
	}

	/**
	 * Verify that the file is within the allowed size
	 *
	 * @return	bool
	 */
	public function is_allowed_filesize()
	{
		if ($this->max_size != 0  AND  $this->file_size > $this->max_size)
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * Verify that the image is within the allowed width/height
	 *
	 * @return	bool
	 */
	public function is_allowed_dimensions()
	{
		if ( ! $this->is_image())
		{
			return TRUE;
		}

		if (function_exists('getimagesize'))
		{
			$D = @getimagesize($this->file_temp);

			if ($this->max_width > 0 AND $D['0'] > $this->max_width)
			{
				return FALSE;
			}

			if ($this->max_height > 0 AND $D['1'] > $this->max_height)
			{
				return FALSE;
			}

			return TRUE;
		}

		return TRUE;
	}

	/**
	 * Extract the file extension
	 *
	 * @param	string
	 * @return	string
	 */
	public function get_extension($filename)
	{
		$x = explode('.', $filename);
		return '.'.end($x);
	}

	/**
	 * Clean the file name for security
	 *
	 * @param	string
	 * @return	string
	 */
	public function clean_file_name($filename)
	{
		$bad = array(
						"<!--",
						"-->",
						"'",
						"<",
						">",
						'"',
						'&',
						'$',
						'=',
						';',
						'?',
						'/',
						"%20",
						"%22",
						"%3c",		// <
						"%253c",	// <
						"%3e",		// >
						"%0e",		// >
						"%28",		// (
						"%29",		// )
						"%2528",	// (
						"%26",		// &
						"%24",		// $
						"%3f",		// ?
						"%3b",		// ;
						"%3d"		// =
					);

		$filename = str_replace($bad, '', $filename);

		return stripslashes($filename);
	}

	/**
	 * Limit the File Name Length
	 *
	 * @param	string
	 * @return	string
	 */
	public function limit_filename_length($filename, $length)
	{
		if (strlen($filename) < $length)
		{
			return $filename;
		}

		$ext = '';
		if (strpos($filename, '.') !== FALSE)
		{
			$parts		= explode('.', $filename);
			$ext		= '.'.array_pop($parts);
			$filename	= implode('.', $parts);
		}

		return substr($filename, 0, ($length - strlen($ext))).$ext;
	}

    /**
     * If possible, will increase PHP's memory limit by the specified number of
     * bytes
     *
     * @param int $size The number of bytes in increase by
     * @return void
     */
    protected function increase_memory_limit($size)
    {
		if (function_exists('memory_get_usage') && memory_get_usage() && ini_get('memory_limit') != '')
		{
			$current = (int) ini_get('memory_limit') * 1024 * 1024;

			// Because 1G is a thing
			if (strtolower(substr(ini_get('memory_limit'), -1)) == 'g')
			{
				$current *= 1024;
			}

			// There was a bug/behavioural change in PHP 5.2, where numbers over
			// one million get output into scientific notation.  number_format()
			// ensures this number is an integer
			// http://bugs.php.net/bug.php?id=43053

			$new_memory = number_format(ceil($size + $current), 0, '.', '');

			// When an integer is used, the value is measured in bytes.
			ini_set('memory_limit', $new_memory);
		}
    }

	/**
	 * Runs the file through the XSS clean function
	 *
	 * This prevents people from embedding malicious code in their files.
	 * I'm not sure that it won't negatively affect certain files in unexpected ways,
	 * but so far I haven't found that it causes trouble.
	 *
	 * @return	void
	 */
	public function do_xss_clean()
	{
		$file = $this->file_temp;

		if (filesize($file) == 0)
		{
			return FALSE;
		}

        $this->increase_memory_limit(filesize($file));

		// If the file being uploaded is an image, then we should have no
		// problem with XSS attacks (in theory), but IE can be fooled into mime-
		// type detecting a malformed image as an html file, thus executing an
		// XSS attack on anyone using IE who looks at the image.  It does this
		// by inspecting the first 255 bytes of an image.  To get around this CI
		// will itself look at the first 255 bytes of an image to determine its
		// relative safety.  This can save a lot of processor power and time if
		// it is actually a clean image, as it will be in nearly all instances
		// _except_ an attempted XSS attack.

		if (function_exists('getimagesize') && ($image = getimagesize($file)) !== FALSE)
		{
			if (ee()->mime_type->fileIsSafeForUpload($file) === FALSE)
			{
				return FALSE; // tricky tricky
			}

			if (($file = @fopen($file, 'rb')) === FALSE) // "b" to force binary
			{
				return FALSE; // Couldn't open the file, return FALSE
			}

			$opening_bytes = fread($file, 256);
			fclose($file);

			// These are known to throw IE into mime-type detection chaos <a,
			// <body, <head, <html, <img, <plaintext, <pre, <script, <table,
			// <title
			// title is basically just in SVG, but we filter it anyhow

			if ( ! preg_match('/<(a|body|head|html|img|plaintext|pre|script|table|title)[\s>]/i', $opening_bytes))
			{
				return TRUE; // its an image, no "triggers" detected in the first 256 bytes, we're good
			}
			else
			{
				return FALSE;
			}
		}

		if (($data = @file_get_contents($file)) === FALSE)
		{
			return FALSE;
		}

		return ee('Security/XSS')->clean($data, TRUE);
	}

	public function do_embedded_php_check()
	{
		$file = $this->file_temp;

		if (filesize($file) == 0)
		{
			return FALSE;
		}

        $this->increase_memory_limit(filesize($file));

		if (($data = @file_get_contents($file)) === FALSE)
		{
			return FALSE;
		}

		// We can't simply check for `<?` because that's valid XML and is
		// allowed in files.
		return (stripos($data, '<?php') === FALSE);
	}

	/**
	 * Set an error message
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_error($msg)
	{
		ee()->lang->load('upload');

		if (is_array($msg))
		{
			foreach ($msg as $val)
			{
				$msg = (ee()->lang->line($val) == FALSE) ? $val : ee()->lang->line($val);
				$this->error_msg[] = $msg;
				log_message('error', $msg);
			}
		}
		else
		{
			$msg = (ee()->lang->line($msg) == FALSE) ? $msg : ee()->lang->line($msg);
			$this->error_msg[] = $msg;
			log_message('error', $msg);
		}
	}

	/**
	 * Display the error message
	 *
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function display_errors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach ($this->error_msg as $val)
		{
			$str .= $open.$val.$close;
		}

		return $str;
	}

	/**
	 * List of Mime Types
	 *
	 * This is a list of mime types.  We use it to validate
	 * the "allowed types" set by the developer
	 *
	 * @param	string
	 * @return	string
	 */
	public function mimes_types($mime)
	{
		ee()->load->library('mime_type');
		return ee()->mime_type->isSafeForUpload($mime);
	}

	/**
	 * Prep Filename
	 *
	 * Prevents possible script execution from Apache's handling of files multiple extensions
	 * http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _prep_filename($filename)
	{
		if (strpos($filename, '.') === FALSE OR $this->allowed_types == '*')
		{
			return $filename;
		}

		$parts		= explode('.', $filename);
		$ext		= array_pop($parts);
		$filename	= array_shift($parts);

		foreach ($parts as $part)
		{
			$filename .= '.'.$part;

			if ( ! $this->allowedType($part))
			{
				$filename .= '_';
			}
		}

		$filename .= '.'.$ext;

		return $filename;
	}

	private function allowedType($extension)
	{
		// numbers by themselves are safe, e.g. file_3.2.5.txt, as are concurrent...dots
		if (ctype_digit($extension) OR $extension == '')
		{
			return TRUE;
		}

		$by_legacy = FALSE;
		$extension = strtolower($extension);

		if (is_array($this->allowed_types))
		{
			$by_legacy = in_array($extension, $this->allowed_types);
		}

		return ($by_legacy OR $this->mimes_types($extension));
	}

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
			$filename_parts = explode('.', $this->file_name);
			$original_parts = explode('.', $original_file);

			if (sizeof($filename_parts) == 1 OR (array_pop($filename_parts) != array_pop($original_parts)))
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

		if ($this->upload_path == '')
		{
			$this->set_error('upload_no_filepath');
			return FALSE;
		}

		if (function_exists('realpath') AND @realpath($this->upload_path) !== FALSE)
		{
			$this->upload_path = str_replace("\\", "/", realpath($this->upload_path));
		}

		if ( ! @is_dir($this->upload_path))
		{
			$this->set_error('upload_no_filepath');
			return FALSE;
		}

		if ( ! is_really_writable($this->upload_path))
		{
			$this->set_error('upload_not_writable');
			return FALSE;
		}

		$this->upload_path = preg_replace("/(.+?)\/*$/", "\\1/",  $this->upload_path);
		return TRUE;
	}

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

		$defaults = array(
							'max_size'			=> 0,
							'max_width'			=> 0,
							'max_height'		=> 0,
							'max_filename'		=> 0,
							'allowed_types'		=> "",
							'file_temp'			=> "",
							'file_name'			=> "",
							'orig_name'			=> "",
							'file_type'			=> "",
							'file_size'			=> "",
							'file_ext'			=> "",
							'upload_path'		=> "",
							'overwrite'			=> FALSE,
							'encrypt_name'		=> FALSE,
							'is_image'			=> FALSE,
							'image_width'		=> '',
							'image_height'		=> '',
							'image_type'		=> '',
							'image_size_str'	=> '',
							'error_msg'			=> array(),
							'mimes'				=> array(),
							'remove_spaces'		=> TRUE,
							'xss_clean'			=> FALSE,
							'temp_prefix'		=> "temp_file_",
							'client_name'		=> ''
						);


		foreach ($defaults as $key => $val)
		{
			if (isset($config[$key]))
			{
				$method = 'set_'.$key;
				if (method_exists($this, $method))
				{
					$this->$method($config[$key]);
				}
				else
				{
					$this->$key = $config[$key];
				}
			}
			else
			{
				$this->$key = $val;
			}
		}

		// if a file_name was provided in the config, use it instead of the user input
		// supplied file name for all uploads until initialized again
		$this->_file_name_override = $this->file_name;
	}

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

// EOF
