<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
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

$link = "<a rel=\"nofollow\" href='".$_GET['URL']."'>".$_GET['URL']."</a>";

if ( $link !== ee()->security->xss_clean($link) )
{
	ee()->load->library('typography');
	show_error(sprintf(lang('redirect_xss_fail'), ee()->typography->encode_email(ee()->config->item('webmaster_email'))));
}


if ($force_redirect == TRUE OR ( ! isset($_SERVER['HTTP_REFERER']) OR ! stristr($_SERVER['HTTP_REFERER'], $host)))
{
	// Possibly not from our site, so we give the user the option
	// Of clicking the link or not
	$str = "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n<title>Redirect</title>\n</head>\n<body>".
			"<p>To proceed to the URL you have requested, click the link below:</p>".
			"<p>$link</p>\n</body>\n</html>";
}
else
{
	$str = "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n<title>Redirect</title>\n".
		   '<meta http-equiv="refresh" content="0; URL='.$_GET['URL'].'">' .
		   "\n</head>\n<body>\n</body>\n</html>";
}

exit($str);


/* End of file Redirect.php */
/* Location: ./system/expressionengine/libraries/Redirect.php */
