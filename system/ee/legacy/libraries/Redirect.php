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
 * URL Redirect
 *
 * The whole purpose of this is to redirect from the control panel with out
 * revealing the control panel URL to the referee.  It should be primarily
 * used when we're redirecting away from the parent site out of the cp, say
 * to an addon's documentation or such.
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

$force_redirect = ($request_type != 'CP' && bool_config_item('force_redirect') == TRUE) ? TRUE: FALSE;

ee()->load->library('typography');

$url = ee()->typography->decodeIDN($_GET['URL']);

$link = '<a rel="nofollow noreferrer" href="'.htmlspecialchars($url, ENT_COMPAT, 'UTF-8').'">Continue</a>';

// Make sure a filtered comparison later doesn't trip the URL as "changed" for URLs with query strings
$link = str_replace('&amp;', '&', $link);

// catch XSS as well as any HTML or malformed URLs. FILTER_VALIDATE_URL doesn't work with IDN,
// so this will also fail if an IDN is used as a redirect on a server that is missing PHP's intl extension,
// but that's okay, as it probably means this redirect was not created by the site owner
if ( ! filter_var($url, FILTER_VALIDATE_URL) OR $link !== ee('Security/XSS')->clean($link) )
{
	show_error(sprintf(lang('redirect_xss_fail'), ee()->typography->encode_email(ee()->config->item('webmaster_email'))));
}

// Make sure all requests to iframe this page are denied
header('X-Frame-Options: SAMEORIGIN');

$referrer_parts = isset($_SERVER['HTTP_REFERER'])
	? parse_url($_SERVER['HTTP_REFERER'])
	: FALSE;

$url_parts = parse_url($url);
$url_host = empty($url_parts['host']) ? '' : $url_parts['host'];

if ($force_redirect == TRUE
	OR ! stristr($url_host, $host) // external link
	OR ( ! $referrer_parts OR ! stristr($referrer_parts['host'], $host)))
{
	// Possibly not from our site, so we give the user the option
	// Of clicking the link or not
	ee()->load->library('view');
	$str = ee('View')->make('ee:errors/redirect')->render([
		'cp_page_title' => 'Redirect',
		'host' => $url_host,
		'url'  => htmlspecialchars($url, ENT_COMPAT, 'UTF-8'),
		'link' => $link,
		'branded' => FALSE,
	]);
}
else
{
	$str = "<html>\n<head>\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>\n<title>Redirect</title>\n".
		   '<meta http-equiv="refresh" content="0; URL='.$_GET['URL'].'">' .
		   "\n</head>\n<body>\n</body>\n</html>";
}

exit($str);

// EOF
