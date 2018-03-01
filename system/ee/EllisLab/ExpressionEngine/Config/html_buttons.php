<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * Pre Defined HTML Buttons
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
												'classname'	 => 'bold'
												),
							'italic'	=> array(
												'tag_name'  => lang('html_btn_italic'),
												'tag_open'  => '<em>',
												'tag_close' => '</em>',
												'accesskey' => 'i',
												'classname'	 => 'italic'
												),
							'strike'	=> array(
												'tag_name'  => lang('html_btn_strike'),
												'tag_open'  => '<del>',
												'tag_close' => '</del>',
												'accesskey' => 's',
												'classname'	 => 'strikethrough'
												),
							'ins'	 	=> array(
												'tag_name'  => lang('html_btn_ins'),
												'tag_open'  => '<ins>',
												'tag_close' => '</ins>',
												'accesskey' => '',
												'classname'	 => 'ins'
												),
							'ul'		=> array(
												'tag_name'  => lang('html_btn_ul'),
												'tag_open'  => '<ul>',
												'tag_close' => '</ul>',
												'accesskey' => 'u',
												'classname'	 => 'list'
												),
							'ol'		=> array(
												'tag_name'  => lang('html_btn_ol'),
												'tag_open'  => '<ol>',
												'tag_close' => '</ol>',
												'accesskey' => 'o',
												'classname'	 => 'olist'
												),
							'blockquote'	=> array(
												'tag_name'  => lang('html_btn_blockquote'),
												'tag_open'  => '<blockquote>',
												'tag_close' => '</blockquote>',
												'accesskey' => 'q',
												'classname'	 => 'quote'
												),
							'anchor'	=> array(
												'tag_name'  => lang('html_btn_anchor'),
												'tag_open'  => '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>',
												'tag_close' => '</a>',
												'accesskey' => 'a',
												'classname'	 => 'link'
												),
							'picture'	=> array(
												'tag_name'  => lang('html_btn_picture'),
												'tag_open'  => '<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />',
												'tag_close' => '',
												'accesskey' => '',
												'classname'	 => 'upload'
												),
							)
);

// EOF
