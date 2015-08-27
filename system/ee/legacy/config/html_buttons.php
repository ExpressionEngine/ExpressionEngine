<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
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
 * @link		http://ellislab.com
 */

$installation_defaults = array('bold', 'italic', 'blockquote', 'anchor', 'picture');

$predefined_buttons = array(
							'bold' 		=> array(
												'tag_name'  => 'Bold text',
												'tag_open'  => '<strong>',
												'tag_close' => '</strong>',
												'accesskey' => 'b',
												'classname'	 => 'html-bold'
												),
							'italic'	=> array(
												'tag_name'  => 'Italic text',
												'tag_open'  => '<em>',
												'tag_close' => '</em>',
												'accesskey' => 'i',
												'classname'	 => 'html-italic'
												),
							'strike'	=> array(
												'tag_name'  => 'Strikethrough text',
												'tag_open'  => '<del>',
												'tag_close' => '</del>',
												'accesskey' => 's',
												'classname'	 => 'html-strike'
												),
							'ins'	 	=> array(
												'tag_name'  => 'Insert text',
												'tag_open'  => '<ins>',
												'tag_close' => '</ins>',
												'accesskey' => '',
												'classname'	 => 'html-ins'
												),
							'ul'		=> array(
												'tag_name'  => 'Unordered list',
												'tag_open'  => '<ul>',
												'tag_close' => '</ul>',
												'accesskey' => 'u',
												'classname'	 => 'html-order-list'
												),
							'ol'		=> array(
												'tag_name'  => 'Ordered list',
												'tag_open'  => '<ol>',
												'tag_close' => '</ol>',
												'accesskey' => 'o',
												'classname'	 => 'html-order-list'
												),
							'blockquote'	=> array(
												'tag_name'  => 'Blockquote',
												'tag_open'  => '<blockquote>',
												'tag_close' => '</blockquote>',
												'accesskey' => 'q',
												'classname'	 => 'html-quote'
												),
							'anchor'	=> array(
												'tag_name'  => 'Link',
												'tag_open'  => '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>',
												'tag_close' => '</a>',
												'accesskey' => 'a',
												'classname'	 => 'html-link'
												),
							'picture'	=> array(
												'tag_name'  => 'Image',
												'tag_open'  => '<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />',
												'tag_close' => '',
												'accesskey' => '',
												'classname'	 => 'html-upload'
												),
							);


/* End of file html_buttons.php */
/* Location: ./system/expressionengine/config/html_buttons.php */
