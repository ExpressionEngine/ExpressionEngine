<?php
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
 * ExpressionEngine Allowed Mime Types
 *
 * These are the mime types that are allowed to be uploaded using the
 * upload class.  For security reasons the list is kept as small as
 * possible.  If you need to upload types that are not in the list you can
 * add them.
 *
 * @package		ExpressionEngine
 * @subpackage	Config
 * @category	Config
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

/*
| -------------------------------------------------------------------
| MIME TYPES
| -------------------------------------------------------------------
| This file contains an array of mime types.  It is used by the
| Upload class to help identify allowed file types.
|
*/
$mimes = array(
				'ai'	=>	'application/postscript',
				'aif'	=>	'audio/x-aiff',
				'aifc'	=>	'audio/x-aiff',
				'aiff'	=>	'audio/x-aiff',
				'avi'	=>	'video/x-msvideo',
				'bin'	=>	'application/macbinary',
				'bmp'	=>	'image/bmp',
				'cpt'	=>	'application/mac-compactpro',
				'css'	=>	'text/css',
				'csv'	=>	array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
				'class'	=>	'application/octet-stream',
				'dcr'	=>	'application/x-director',
				'dir'	=>	'application/x-director',
				'dll'	=>	'application/octet-stream',
				'dms'	=>	'application/octet-stream',
				'doc'	=>	'application/msword',
				'docm'	=>	'application/vnd.ms-word.document.macroEnabled.12',
				'docx'	=>	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.wordprocessingml'),
				'dotm'	=>	'application/vnd.ms-word.template.macroEnabled.12',
				'dotx'	=>	'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
				'dvi'	=>	'application/x-dvi',
				'dxr'	=>	'application/x-director',
				'eml'	=>	'message/rfc822',
				'eps'	=>	'application/postscript',
				'epub'	=>	'application/epub+zip',
				'exe'	=>	'application/octet-stream',
				'flac'	=>	'audio/flac',
				'gif'	=>	'image/gif',
				'gtar'	=>	'application/x-gtar',
				'gz'	=>	'application/x-gzip',
				'hqx'	=>	'application/mac-binhex40',
				'htm'	=>	'text/html',
				'html'	=>	'text/html',
				'jpg'	=>	array('image/jpeg', 'image/pjpeg'),
				'jpe'	=>	array('image/jpeg', 'image/pjpeg'),
				'jpeg'	=>	array('image/jpeg', 'image/pjpeg'),
				'js'	=>	'application/x-javascript',
				'lha'	=>	'application/octet-stream',
				'log'	=>	array('text/plain', 'text/x-log'),
				'lzh'	=>	'application/octet-stream',
				'm4a'	=>	'audio/m4a',
				'm4v'	=>	'video/m4v',
				'mid'	=>	'audio/midi',
				'midi'	=>	'audio/midi',
				'mif'	=>	'application/vnd.mif',
				'mov'	=>	'video/quicktime',
				'movie'	=>	'video/x-sgi-movie',
				'mp2'	=>	'audio/mpeg',
				'mp3'	=>	array('audio/mpeg', 'audio/mpg'),
				'mp4'	=>	array('audio/mp4', 'video/mp4'),
				'mpe'	=>	'video/mpeg',
				'mpeg'	=>	'video/mpeg',
				'mpg'	=>	'video/mpeg',
				'mpga'	=>	'audio/mpeg',
				'oda'	=>	'application/oda',
				'odb'	=>	'application/vnd.oasis.opendocument.database',
				'odc'	=>	'application/vnd.oasis.opendocument.chart',
				'odf'	=>	'application/vnd.oasis.opendocument.formula',
				'odg'	=>	'application/vnd.oasis.opendocument.graphics',
				'odi'	=>	'application/vnd.oasis.opendocument.image',
				'odm'	=>	'application/vnd.oasis.opendocument.text-master',
				'odp'	=>	'application/vnd.oasis.opendocument.presentation',
				'ods'	=>	'application/vnd.oasis.opendocument.spreadsheet',
				'odt'	=>	'application/vnd.oasis.opendocument.text',
				'ogg'	=>	'audio/ogg',
				'ogv'	=>	'video/ogg',
				'otc'	=>  'application/vnd.oasis.opendocument.chart-template',
				'otf'	=>  'application/vnd.oasis.opendocument.formula-template',
				'otg'	=>  'application/vnd.oasis.opendocument.graphics-template',
				'oth'	=>  'application/vnd.oasis.opendocument.text-web',
				'oti'	=>  'application/vnd.oasis.opendocument.image-template',
				'otp'	=>  'application/vnd.oasis.opendocument.presentation-template',
				'ots'	=>  'application/vnd.oasis.opendocument.spreadsheet-template',
				'ott'	=>  'application/vnd.oasis.opendocument.text-template',
				'pdf'	=>	array('application/pdf', 'application/x-download'),
				'php'	=>	'application/x-httpd-php',
				'php3'	=>	'application/x-httpd-php',
				'php4'	=>	'application/x-httpd-php',
				'phps'	=>	'application/x-httpd-php-source',
				'phtml'	=>	'application/x-httpd-php',
				'png'	=>	array('image/png',  'image/x-png'),
				'potm'	=>	'application/vnd.ms-powerpoint.template.macroEnabled.12',
				'potx'	=>	'application/vnd.openxmlformats-officedocument.presentationml.template',
				'ppam'	=>	'application/vnd.ms-powerpoint.addin.macroEnabled.12',
				'ppsm'	=>	'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
				'ppsx'	=>	'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
				'ppt'	=>	array('application/powerpoint', 'application/vnd.ms-powerpoint'),
				'pptm'	=>	'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
				'pptx'	=>	'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'ps'	=>	'application/postscript',
				'psd'	=>	'application/x-photoshop',
				'qt'	=>	'video/quicktime',
				'ra'	=>	'audio/x-realaudio',
				'ram'	=>	'audio/x-pn-realaudio',
				'rar'	=>	'application/x-rar-compressed',
				'rm'	=>	'audio/x-pn-realaudio',
				'rpm'	=>	'audio/x-pn-realaudio-plugin',
				'rtx'	=>	'text/richtext',
				'rtf'	=>	'text/rtf',
				'rv'	=>	'video/vnd.rn-realvideo',
				'sea'	=>	'application/octet-stream',
				'shtml'	=>	'text/html',
				'sit'	=>	'application/x-stuffit',
				'smi'	=>	'application/smil',
				'smil'	=>	'application/smil',
				'so'	=>	'application/octet-stream',
				'swf'	=>	'application/x-shockwave-flash',
				'tar'	=>	'application/x-tar',
				'text'	=>	'text/plain',
				'tgz'	=>	'application/x-tar',
				'tif'	=>	'image/tiff',
				'tiff'	=>	'image/tiff',
				'txt'	=>	'text/plain',
				'wav'	=>	'audio/x-wav',
				'wbxml'	=>	'application/wbxml',
				'webm'	=>	'video/webm',
				'wmlc'	=>	'application/wmlc',
				'word'	=>	array('application/msword', 'application/octet-stream'),
				'wmv' 	=> 	'video/x-ms-wmv',
				'xht'	=>	'application/xhtml+xml',
				'xhtml'	=>	'application/xhtml+xml',
				'xl'	=>	'application/excel',
				'xlam'	=>	'application/vnd.ms-excel.addin.macroEnabled.12',
				'xlsb'	=>	'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
				'xlsm'	=>	'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
				'xlsb'	=>	'application/vnd.ms-excel.sheet.macroEnabled.12',
				'xls'	=>	array('application/excel', 'application/vnd.ms-excel'),
				'xlsx'	=>	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'xltm'	=>	'application/vnd.ms-excel.template.macroEnabled.12',
				'xltx'	=>	'application/vnd.openxmlformats-officedocument.spreadsheetml',
				'xml'	=>	'text/xml',
				'xps'	=>	'application/vnd.ms-xpsdocument',
				'xsl'	=>	'text/xml',
				'zip'	=>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed')
			);

/* End of file CI_mimes.php */
/* Location: ./system/expressionengine/config/mimes.php */