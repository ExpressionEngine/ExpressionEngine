<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------


/**
 * URL Redirect
 *
 * The whole purpose of this is to redirect from the control panel with out
 * revealing the control panel URL to the referee.  It should be primarily
 * used when we're redirecting away from the parent site out of the cp, say
 * to an addon's documentation or such.
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
if ( ! isset($_GET['URL']))
{
	exit();
}


if (strncmp($_GET['URL'], 'http', 4) != 0 && strpos($_GET['URL'], '://') === FALSE && substr($_GET['URL'], 0, 1) != '/')
{
	$_GET['URL'] = "http://".$_GET['URL'];
}


$host = ( ! isset($_SERVER['HTTP_HOST'])) ? '' : (substr($_SERVER['HTTP_HOST'],0,4) == 'www.' ? substr($_SERVER['HTTP_HOST'], 4) : $_SERVER['HTTP_HOST']);

$force_redirect = ($request_type != 'CP' && config_item('force_redirect') == TRUE) ? TRUE: FALSE;

ee()->load->library('typography');

$url = ee()->typography->decodeIDN($_GET['URL']);

$link = '<a rel="nofollow" href="'.htmlspecialchars($url, ENT_COMPAT, 'UTF-8').'">Continue to the new page</a>';

// catch XSS as well as any HTML or malformed URLs. FILTER_VALIDATE_URL doesn't work with IDN,
// so this will also fail if an IDN is used as a redirect on a server that is missing PHP's intl extension,
// but that's okay, as it probably means this redirect was not created by the site owner
if ( ! filter_var($url, FILTER_VALIDATE_URL) OR $link !== ee('Security/XSS')->clean($link) )
{
	show_error(sprintf(lang('redirect_xss_fail'), ee()->typography->encode_email(ee()->config->item('webmaster_email'))));
}

// Make sure all requests to iframe this page are denied
header('X-Frame-Options: SAMEORIGIN');

if ($force_redirect == TRUE OR ( ! isset($_SERVER['HTTP_REFERER']) OR ! stristr($_SERVER['HTTP_REFERER'], $host)))
{
	// Possibly not from our site, so we give the user the option
	// Of clicking the link or not
	$str = "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n<meta name='robots' content='none'>\n<title>Redirect</title>\n</head>\n<body>".
			"<p>Warning: You’re opening a new web page ($url) that is not part of ".config_item('site_label').". Double check that the web page address is correct.</p>".
			"<p>Would you like to $link or <a href='".config_item('site_url')."'>Stay put</a>?</p>\n</body>\n</html>";
}
else
{
	$str = "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n<title>Redirect</title>\n".
		   '<meta http-equiv="refresh" content="0; URL='.$_GET['URL'].'">' .
		   "\n</head>\n<body>\n</body>\n</html>";
}

exit($str);

// EOF
