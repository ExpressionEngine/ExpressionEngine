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
 * HTTP Header Plugin
 */
class Http_header {

	public $return_data;

	public function __construct()
	{
		$allowed_header_fields = [
			'access_control_allow_credentials' => 'Access-Control-Allow-Credentials',
			'access_control_allow_headers'     => 'Access-Control-Allow-Headers',
			'access_control_allow_methods'     => 'Access-Control-Allow-Methods',
			'access_control_allow_origin'      => 'Access-Control-Allow-Origin',
			'access_control_expose_headers'    => 'Access-Control-Expose-Headers',
			'access_control_max_age'           => 'Access-Control-Max-Age',
			'alt_svc'                          => 'Alt-Svc',
			'cache_control'                    => 'Cache-Control',
			'content_disposition'              => 'Content-Disposition',
			'content_encoding'                 => 'Content-Encoding',
			'content_language'                 => 'Content-Language',
			'content_length'                   => 'Content-Length',
			'content_location'                 => 'Content-Location',
			'content_md5'                      => 'Content-MD5',
			'content_range'                    => 'Content-Range',
			'content_type'                     => 'Content-Type',
			'etag'                             => 'ETag',
			'expires'                          => 'Expires',
			'last_modified'                    => 'Last-Modified',
			'link'                             => 'Link',
			'location'                         => 'Location',
			'pragma'                           => 'Pragma',
			'refresh'                          => 'Refresh',
			'retry_after'                      => 'Retry-After',
			'status'                           => 'Status',
			'tk'                               => 'Tk',
			'vary'                             => 'Vary',
			'via'                              => 'Via',
			'warning'                          => 'Warning',
			'x_content_duration'               => 'X-Content-Duration',
			'x_content_type_options'           => 'X-Content-Type-Options',
			'x_frame_options'                  => 'X-Frame-Options',
			'x_ua_compatible'                  => 'X-UA-Compatible',
		];

		$class_methods = get_class_methods($this);

		foreach (ee()->TMPL->tagparams as $key => $value)
		{
			$set_header = TRUE;
			$key = strtolower($key);
			$value = $this->parseTags($value);

			if (array_key_exists($key, $allowed_header_fields))
			{
				if (in_array("set_{$key}", $class_methods))
				{
					$set_header = $this->{'set_'.$key}($value);
				}

				if ($set_header)
				{
					ee('Response')->setHeader($allowed_header_fields[$key], $value);
				}
			}
		}

		return;
	}

	/**
	 * If the 'filename' part of the Content-Disposition header is missing
	 * we'll look for a parameter and set the header. Otherwise we'll simply
	 * regurgitate the value
	 *
	 * @param string $value The value of the content_disposition parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_content_disposition($value)
	{
		$parts = explode('; ', $value);

		// When the value is "attachment" look and see if the filename was
		// supplied in the value, otherwise look for a "filename" tag parameter.
		// If we find that parameter we'll craft our own header.
		if (strtolower($parts[0]) == 'attachment')
		{
			if ( ! isset($parts[1]))
			{
				$filename = ee()->TMPL->fetch_param('filename');

				if ($filename)
				{
					ee('Response')->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * If the 'charset' part of the Content-Type header is missing
	 * we'll look for a parameter and set the header. Otherwise we'll simply
	 * regurgitate the value
	 *
	 * @param string $value The value of the content_type parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_content_type($value)
	{
		$parts = explode('; ', $value);

		// Check to see if the charset was supplied in the value. If not look for
		// a "charset" tag parameter, and if found we'll craft our own header.
		if ( ! isset($parts[1]))
		{
			$charset = ee()->TMPL->fetch_param('charset');

			if ($charset)
			{
				ee('Response')->setHeader('Content-Type', "{$parts[0]}; charset={$charset}");
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Attempts to set the Expires header with the proper date format.
	 *
	 * @param string $value The value of the expires parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_expires($value)
	{
		ee('Response')->setHeader('Expires', $this->parseDateString($value));
		return FALSE;
	}

	/**
	 * Attempts to set the Last-Modified header with the proper date format.
	 *
	 * @param string $value The value of the last_modified parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_last_modified($value)
	{
		ee('Response')->setHeader('Last-Modified', $this->parseDateString($value));
		return FALSE;
	}

	/**
	 * Uses the redirect method inside EE for consistency's sake
	 *
	 * @param string $value The value of the last_modified parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_location($value)
	{
		$status = ee()->TMPL->fetch_param('status', NULL);

		ee()->functions->redirect($value, FALSE, $status);

		// Yes, the redirect method currently ends with `exit;` but: defensive coding
		return FALSE;
	}

	/**
	 * If the 'url' part of the Refresh header is missing
	 * we'll look for a parameter and set the header. Otherwise we'll simply
	 * regurgitate the value
	 *
	 * @param string $value The value of the refresh parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_refresh($value)
	{
		$parts = explode('; ', $value);

		// Check to see if the charset was supplied in the value. If not look for
		// a "charset" tag parameter, and if found we'll craft our own header.
		if ( ! isset($parts[1]))
		{
			$url = ee()->TMPL->fetch_param('url');

			if ($url)
			{
				$url = $this->parseTags($url);
				ee('Response')->setHeader('Refresh', "{$parts[0]}; url={$url}");
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * The Retry-After header can either be a number of seconds or a discrete date.
	 * If we were not supplied with a number we'll attempt to set the header with
	 * the proper date format.
	 *
	 * @param string $value The value of the retry_after parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_retry_after($value)
	{
		if ( ! is_numeric($value))
		{
			ee('Response')->setHeader('Retry-After', $this->parseDateString($value));
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * If the $value is numeric then we'll use EE's set_status_header, which will supply
	 * the text to match the value. Otherwise we'll simply regurgitate the value.
	 *
	 * @param string $value The value of the status parameter
	 * @param bool TRUE if a header needs to be set; FALSE if not
	 */
	private function set_status($value)
	{
		if (is_numeric($value))
		{
			ee('Response')->setStatus($value);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Takes a string and attempts to convert it to an HTTP-date (RFC-7231)
	 *
	 * @param string $string The date string
	 * @return string An RFC-7231 formatted date or the $string if it could not be
	 *   parsed.
	 */
	private function parseDateString($string)
	{
		$timestamp = strtotime($string);

		if ($timestamp === FALSE)
		{
			return $timestamp;
		}

		return gmdate("D, d M Y H:i:s", $timestamp) . ' GMT';
	}

	/**
	 * If the value appears to have tags in it we'll run it through the parse_globals
	 * method.
	 *
	 * @param string $value The header value
	 * @return string The header with the tags parsed and replaced.
	 */
	private function parseTags($value)
	{
		if (strpos($value, '{') !== FALSE && strpos($value, '}') !== FALSE)
		{
			return ee()->TMPL->parse_globals($value);
		}

		return $value;
 	}

}
// END CLASS

// EOF
