<?php

namespace EllisLab\ExpressionEngine\Library\Mime;

use Exception;
use InvalidArgumentException;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.10.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Mime Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class MimeType {

	protected $whitelist = array();
	protected $images    = array();

	/**
	 * Constructor
	 *
	 * @param array $mimes An array of MIME types to add to the whitelist
	 * @return void
	 */
	public function __construct(array $mimes = array())
	{
		$this->addMimeTypes($mimes);
	}

	/**
	 * Adds a mime type to the whitelist
	 *
	 * @param string $mime The mime type to add to the whitelist
	 * @return void
	 */
	public function addMimeType($mime)
	{
		if ( ! is_string($mime))
		{
			throw new InvalidArgumentException("addMimeType only accepts strings; " . gettype($mime) . " found instead.");
		}

		if (count(explode('/', $mime)) != 2)
		{
			throw new InvalidArgumentException($mime . " is not a valid MIME type.");
		}

		if ( ! in_array($mime, $this->whitelist))
		{
			$this->whitelist[] = $mime;

			if (strpos($mime, 'image/') === 0)
			{
				$this->images[] = $mime;
			}
		}
	}

	/**
	 * Adds multiple mime types to the whitelist
	 *
	 * @param array $mimes An array of MIME types to add to the whitelist
	 * @return void
	 */
	public function addMimeTypes(array $mimes)
	{
		foreach ($mimes as $mime)
		{
			$this->addMimeType($mime);
		}
	}

	/**
	 * Returns the whitelist of MIME Types
	 *
	 * @return array An array of MIME types that are on the whitelist
	 */
	public function getWhitelist()
	{
		return $this->whitelist;
	}

	/**
	 * Determines the MIME type of a file
	 *
	 * @throws Exception If the file does not exist
	 * @param string $path The full path to the file being checked
	 * @return string The MIME type of the file
	 */
	public function ofFile($path)
	{
		if ( ! file_exists($path))
		{
			throw new Exception("File " . $path . " does not exist.");
		}

		// Set a default
		$mime = 'application/octet-stream';

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		if ($finfo !== FALSE)
		{
			$fres = @finfo_file($finfo, $path);
			if ( ($fres !== FALSE)
				&& is_string($fres)
				&& (strlen($fres)>0))
			{
				$mime = $fres;
			}

			@finfo_close($finfo);
		}

		// A few files are identified as plain text, which while true is not as
		// helpful as which type of plain text files they are.
		if ($mime == 'text/plain')
		{
			$parts = explode('.', $path);
			$extension = end($parts);

			switch ($extension)
			{
				case 'css':
					$mime = 'text/css';
					break;

				case 'js':
					$mime = 'application/javascript';
					break;

				case 'json':
					$mime = 'application/json';
					break;
			}
		}

		return $mime;
	}

	/**
	 * Determines the MIME type of a buffer
	 *
	 * @param string $buffer The buffer/data to check
	 * @return string The MIME type of the buffer
	 */
	public function ofBuffer($buffer)
	{
		// Set a default
		$mime = 'application/octet-stream';

		$finfo = @finfo_open(FILEINFO_MIME_TYPE);
		if ($finfo !== FALSE)
		{
			$fres = @finfo_buffer($finfo, $buffer);
			if ( ($fres !== FALSE)
				&& is_string($fres)
				&& (strlen($fres)>0))
			{
				$mime = $fres;
			}

			@finfo_close($finfo);
		}

		return $mime;
	}

	/**
	 * Determines if a file is an image or not.
	 *
	 * @throws Exception If the file does not exist
	 * @param string $path The full path to the file being checked
	 * @return bool TRUE if it is an image; FALSE if not
	 */
	public function fileIsImage($path)
	{
		return $this->isImage($this->ofFile($path));
	}

	/**
	 * Determines if a MIME type is in our list of valid image MIME types.
	 *
	 * @param string $mime The mime to check
	 * @return bool TRUE if it is an image; FALSE if not
	 */
	public function isImage($mime)
	{
		return in_array($mime, $this->images, TRUE);
	}

	/**
	 * Gets the MIME type of a file and compares it to our whitelist to see if
	 * it is safe for upload.
	 *
	 * @throws Exception If the file does not exist
	 * @param string $path The full path to the file being checked
	 * @return bool TRUE if it safe; FALSE if not
	 */
	public function fileIsSafeForUpload($path)
	{
		return $this->isSafeForUpload($this->ofFile($path));
	}

	/**
	 * Checks a given MIME type against our whitelist to see if it is safe for
	 * upload
	 *
	 * @param string $mime The mime to check
	 * @return bool TRUE if it is an image; FALSE if not
	 */
	public function isSafeForUpload($mime)
	{
		return in_array($mime, $this->whitelist, TRUE);
	}

}

// EOF
