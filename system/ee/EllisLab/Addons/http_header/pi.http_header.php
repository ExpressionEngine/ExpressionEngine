<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
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
		// @TODO Avoid this hack for the content type header
		ee()->TMPL->template_type = 'webpage-with-custom-headers';

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

	// private function set_expires($value)

	// private function set_last_modified($value)

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

	// private function set_retry_after($value)

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
			// The Output class always sets the status header, so if we want a custom
			// header we'll need to disable that
			// @TODO Avoid this hack
			ee()->config->set_item('send_headers', FALSE);

			// We don't want to override these if they've already been set
			if ( ! ee('Response')->hasHeader('Expires'))
			{
				ee('Response')->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
			}

			if ( ! ee('Response')->hasHeader('Last-Modified'))
			{
				ee('Response')->setHeader('Last-Modified', gmdate("D, d M Y H:i:s") . ' GMT');
			}

			if ( ! ee('Response')->hasHeader('Pragma'))
			{
				ee('Response')->setHeader('Pragma', 'no-cache');
			}

			set_status_header($value);
			return FALSE;
		}

		return TRUE;
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
