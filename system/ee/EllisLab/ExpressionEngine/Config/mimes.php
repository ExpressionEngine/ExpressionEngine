<?php
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
 * @link		https://ellislab.com
 */

// $whitelist =
return array(
	'application/csv', // .csv
	'application/epub+zip', // .epub
	'application/excel', // .csv, .xl, .xls
	'application/mac-binhex40', // .hqx
	'application/mac-compactpro', // .cpt
	'application/msword', // .doc, .word
	'application/oda', // .oda
	'application/ogg', // .ogv
	'application/pdf', // .pdf
	'application/postscript', // .ai, .eps, .ps
	'application/powerpoint', // .ppt
	'application/smil', // .smi, .smil
	'application/vnd.mif', // .mif
	'application/vnd.ms-excel', // .csv, .xls
	'application/vnd.ms-excel.addin.macroEnabled.12', // .xlam
	'application/vnd.ms-excel.sheet.binary.macroEnabled.12', // .xlsm
	'application/vnd.ms-excel.sheet.macroEnabled.12', // .xlsb
	'application/vnd.ms-excel.template.macroEnabled.12', // .xltm
	'application/vnd.ms-powerpoint', // .ppt
	'application/vnd.ms-powerpoint.addin.macroEnabled.12', // .ppam
	'application/vnd.ms-powerpoint.presentation.macroEnabled.12', // .pptm
	'application/vnd.ms-powerpoint.slideshow.macroEnabled.12', // .ppsm
	'application/vnd.ms-powerpoint.template.macroEnabled.12', // .potm
	'application/vnd.ms-word.document.macroEnabled.12', // .docm
	'application/vnd.ms-word.template.macroEnabled.12', // .dotm
	'application/vnd.ms-xpsdocument', // .xps
	'application/vnd.msexcel', // .csv
	'application/vnd.oasis.opendocument.chart', // .odc
	'application/vnd.oasis.opendocument.chart-template', // .otc
	'application/vnd.oasis.opendocument.database', // .odb
	'application/vnd.oasis.opendocument.formula', // .odf
	'application/vnd.oasis.opendocument.formula-template', // .otf
	'application/vnd.oasis.opendocument.graphics', // .odg
	'application/vnd.oasis.opendocument.graphics-template', // .otg
	'application/vnd.oasis.opendocument.image', // .odi
	'application/vnd.oasis.opendocument.image-template', // .oti
	'application/vnd.oasis.opendocument.presentation', // .odp
	'application/vnd.oasis.opendocument.presentation-template', // .otp
	'application/vnd.oasis.opendocument.spreadsheet', // .ods
	'application/vnd.oasis.opendocument.spreadsheet-template', // .ots
	'application/vnd.oasis.opendocument.text', // .odt
	'application/vnd.oasis.opendocument.text-master', // .odm
	'application/vnd.oasis.opendocument.text-template', // .ott
	'application/vnd.oasis.opendocument.text-web', // .oth
	'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx
	'application/vnd.openxmlformats-officedocument.presentationml.slideshow', // .ppsx
	'application/vnd.openxmlformats-officedocument.presentationml.template', // .potx
	'application/vnd.openxmlformats-officedocument.spreadsheetml', // .xltx
	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
	'application/vnd.openxmlformats-officedocument.wordprocessingml', // .docx
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
	'application/vnd.openxmlformats-officedocument.wordprocessingml.template', // .dotx
	'application/wbxml', // .wbxml
	'application/wmlc', // .wmlc
	'application/x-director', // .dcr, .dir, .dxr
	'application/x-download', // .pdf
	'application/x-dvi', // .dvi
	'application/x-gtar', // .gtar
	'application/x-gzip', // .gz
	'application/x-photoshop', // .psd
	'application/x-rar-compressed', // .rar
	'application/x-stuffit', // .sit
	'application/x-tar', // .tar, .tgz
	'application/x-zip', // .zip
	'application/x-zip-compressed', // .zip
	'application/xhtml+xml', // .xht, .xhtml
	'application/zip', // .zip
	'audio/flac', // .flac
	'audio/m4a', // .m4a
	'audio/midi', // .mid, .midi
	'audio/mp4', // .mp4
	'audio/mpeg', // .mp2, .mp3, .mpga
	'audio/mpg', // .mp3
	'audio/ogg', // .ogg
	'audio/x-aiff', // .aif, .aifc, .aiff
	'audio/x-pn-realaudio', // .ram, .rm
	'audio/x-pn-realaudio-plugin', // .rpm
	'audio/x-realaudio', // .ra
	'audio/x-wav', // .wav
	'image/bmp', // .bmp
	'image/gif', // .gif
	'image/jpeg', // .jpg, .jpe, .jpeg
	'image/pjpeg', // .jpg, .jpe, .jpeg
	'image/png', // .png
	'image/svg+xml', // .svg
	'image/tiff', // .tif, .tiff
	'image/x-png', // .png
	'message/rfc822', // .eml
	'text/comma-separated-values', // .csv
	'text/css', // .css
	'text/csv', // .csv
	'text/html', // .html, .htm
	'text/plain', // .log, .text, .txt
	'text/richtext', // .rtx
	'text/rtf', // .rtf
	'text/x-comma-separated-values', // .csv
	'text/x-log', // .log
	'text/xml', // .xml, .xsl
	'video/m4v', // .m4v
	'video/mp4', // .mp4
	'video/mpeg', // .mpe, .mpeg, .mpg
	'video/ogg', // .ogv
	'video/quicktime', // .mov, .qt
	'video/vnd.rn-realvideo', // .rv
	'video/webm', // .webm
	'video/x-ms-wmv', // .wmv
	'video/x-msvideo', // .avi
	'video/x-sgi-movie', // .movie
);

// EOF
