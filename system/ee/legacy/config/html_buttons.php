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
												'tag_name'  => 'b',
												'tag_open'  => '<strong>',
												'tag_close' => '</strong>',
												'accesskey' => 'b',
												'classname'	 => 'html-bold'
												),
							'italic'	=> array(
												'tag_name'  => 'i',
												'tag_open'  => '<em>',
												'tag_close' => '</em>',
												'accesskey' => 'i',
												'classname'	 => 'html-italic'
												),
							'strike'	=> array(
												'tag_name'  => 's',
												'tag_open'  => '<del>',
												'tag_close' => '</del>',
												'accesskey' => 's',
												'classname'	 => 'strikethrough'
												),
							'ins'	 	=> array(
												'tag_name'  => 'insert',
												'tag_open'  => '<ins>',
												'tag_close' => '</ins>',
												'accesskey' => '',
												'classname'	 => 'btn_ins'
												),
							'ul'		=> array(
												'tag_name'  => 'ul',
												'tag_open'  => '<ul>',
												'tag_close' => '</ul>',
												'accesskey' => 'u',
												'classname'	 => 'html-order-list'
												),
							'ol'		=> array(
												'tag_name'  => 'ol',
												'tag_open'  => '<ol>',
												'tag_close' => '</ol>',
												'accesskey' => 'o',
												'classname'	 => 'html-order-list'
												),
							'blockquote'	=> array(
												'tag_name'  => 'blockquote',
												'tag_open'  => '<blockquote>',
												'tag_close' => '</blockquote>',
												'accesskey' => 'q',
												'classname'	 => 'html-quote'
												),
							'anchor'	=> array(
												'tag_name'  => 'a',
												'tag_open'  => '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>',
												'tag_close' => '</a>',
												'accesskey' => 'a',
												'classname'	 => 'html-link'
												),
							'picture'	=> array(
												'tag_name'  => 'img',
												'tag_open'  => '<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />',
												'tag_close' => '',
												'accesskey' => '',
												'classname'	 => 'html-upload'
												),
							);


/* End of file html_buttons.php */
/* Location: ./system/expressionengine/config/html_buttons.php */
