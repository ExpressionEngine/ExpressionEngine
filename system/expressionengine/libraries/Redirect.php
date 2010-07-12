<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * URL Redirect
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
if ( ! isset($_GET['URL'])) 
{ 
	exit();
}

$_GET['URL'] = str_replace(array("\r", "\r\n", "\n", '%3A','%3a','%2F','%2f'), array('', '', '', ':', ':', '/', '/'), $_GET['URL']);

if (strncmp($_GET['URL'], 'http', 4) != 0 && strpos($_GET['URL'], '://') === FALSE && substr($_GET['URL'], 0, 1) != '/')
{
	$_GET['URL'] = "http://".$_GET['URL']; 
}
	
$_GET['URL'] = str_replace( array('"', "'", ')', '(', ';', '}', '{', 'script%', 'script&', '&#40', '&#41'), '', strip_tags($_GET['URL']));

$host = ( ! isset($_SERVER['HTTP_HOST'])) ? '' : (substr($_SERVER['HTTP_HOST'],0,4) == 'www.' ? substr($_SERVER['HTTP_HOST'], 4) : $_SERVER['HTTP_HOST']);

if ( ! isset($_SERVER['HTTP_REFERER']) OR ! stristr($_SERVER['HTTP_REFERER'], $host))
{
	// Possibly not from our site, so we give the user the option
	// Of clicking the link or not
	
	$str = "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n<title>Redirect</title>\n</head>\n<body>".
			"<p>To proceed to the URL you have requested, click the link below:</p>".
			"<p><a href='".$_GET['URL']."'>".$_GET['URL']."</a></p>\n</body>\n</html>";
}
else
{
	$str = "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n<title>Redirect</title>\n".
		   '<meta http-equiv="refresh" content="0; URL='.$_GET['URL'].'">'.
		   "\n</head>\n<body>\n</body>\n</html>";
}

exit($str);


/* End of file Redirect.php */
/* Location: ./system/expressionengine/libraries/Redirect.php */