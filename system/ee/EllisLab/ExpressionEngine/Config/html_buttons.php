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
 * ExpressionEngine Pre Defined HTML Buttons
 *
 * @package		ExpressionEngine
 * @subpackage	Config
 * @category	Config
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */


// $installation_defaults
// $predefined_buttons

return array(
	'defaults' => array('bold', 'italic', 'blockquote', 'anchor', 'picture'),
	'buttons' => array(
							'bold' 		=> array(
												'tag_name'  => lang('html_btn_bold'),
												'tag_open'  => '<strong>',
												'tag_close' => '</strong>',
												'accesskey' => 'b',
												'classname'	 => 'html-bold'
												),
							'italic'	=> array(
												'tag_name'  => lang('html_btn_italic'),
												'tag_open'  => '<em>',
												'tag_close' => '</em>',
												'accesskey' => 'i',
												'classname'	 => 'html-italic'
												),
							'strike'	=> array(
												'tag_name'  => lang('html_btn_strike'),
												'tag_open'  => '<del>',
												'tag_close' => '</del>',
												'accesskey' => 's',
												'classname'	 => 'html-strike'
												),
							'ins'	 	=> array(
												'tag_name'  => lang('html_btn_ins'),
												'tag_open'  => '<ins>',
												'tag_close' => '</ins>',
												'accesskey' => '',
												'classname'	 => 'html-ins'
												),
							'ul'		=> array(
												'tag_name'  => lang('html_btn_ul'),
												'tag_open'  => '<ul>',
												'tag_close' => '</ul>',
												'accesskey' => 'u',
												'classname'	 => 'html-list'
												),
							'ol'		=> array(
												'tag_name'  => lang('html_btn_ol'),
												'tag_open'  => '<ol>',
												'tag_close' => '</ol>',
												'accesskey' => 'o',
												'classname'	 => 'html-order-list'
												),
							'blockquote'	=> array(
												'tag_name'  => lang('html_btn_blockquote'),
												'tag_open'  => '<blockquote>',
												'tag_close' => '</blockquote>',
												'accesskey' => 'q',
												'classname'	 => 'html-quote'
												),
							'anchor'	=> array(
												'tag_name'  => lang('html_btn_anchor'),
												'tag_open'  => '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>',
												'tag_close' => '</a>',
												'accesskey' => 'a',
												'classname'	 => 'html-link'
												),
							'picture'	=> array(
												'tag_name'  => lang('html_btn_picture'),
												'tag_open'  => '<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />',
												'tag_close' => '',
												'accesskey' => '',
												'classname'	 => 'html-upload'
												),
							)
);

// EOF
