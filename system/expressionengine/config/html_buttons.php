<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
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
												'classname'	 => 'btn_b'
												),
							'italic'	=> array(
												'tag_name'  => 'i',
												'tag_open'  => '<em>',
												'tag_close' => '</em>',
												'accesskey' => 'i',
												'classname'	 => 'btn_i'
												),
							'strike'	=> array(
												'tag_name'  => 's',
												'tag_open'  => '<del>',
												'tag_close' => '</del>',
												'accesskey' => 's',
												'classname'	 => 'btn_strike'
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
												'classname'	 => 'btn_ul'
												),
							'ol'		=> array(
												'tag_name'  => 'ol',
												'tag_open'  => '<ol>',
												'tag_close' => '</ol>',
												'accesskey' => 'o',
												'classname'	 => 'btn_ol'
												),
							'li'		=> array(
												'tag_name'  => 'li',
												'tag_open'  => '<li>',
												'tag_close' => '</li>',
												'accesskey' => 'o',
												'classname'	 => 'btn_li'
												),
							'p'			=> array(
												'tag_name'  => 'p',
												'tag_open'  => '<p>',
												'tag_close' => '</p>',
												'accesskey' => 'p',
												'classname'	 => 'btn_p'
												),
							'blockquote'	=> array(
												'tag_name'  => 'blockquote',
												'tag_open'  => '<blockquote>',
												'tag_close' => '</blockquote>',
												'accesskey' => 'q',
												'classname'	 => 'btn_blockquote'
												),
							'h1'		=> array(
												'tag_name'  => 'h1',
												'tag_open'  => '<h1>',
												'tag_close' => '</h1>',
												'accesskey' => '',
												'classname'	 => 'btn_h1'
												),
							'h2'		=> array(
												'tag_name'  => 'h2',
												'tag_open'  => '<h2>',
												'tag_close' => '</h2>',
												'accesskey' => '',
												'classname'	 => 'btn_h2'
												),
							'h3'		=> array(
												'tag_name'  => 'h3',
												'tag_open'  => '<h3>',
												'tag_close' => '</h3>',
												'accesskey' => '',
												'classname'	 => 'btn_h3'
												),
							'h4'		=> array(
												'tag_name'  => 'h4',
												'tag_open'  => '<h4>',
												'tag_close' => '</h4>',
												'accesskey' => '',
												'classname'	 => 'btn_h4'
												),
							'h5'		=> array(
												'tag_name'  => 'h5',
												'tag_open'  => '<h5>',
												'tag_close' => '</h5>',
												'accesskey' => '',
												'classname'	 => 'btn_h5'
												),
							'h6'		=> array(
												'tag_name'  => 'h6',
												'tag_open'  => '<h6>',
												'tag_close' => '</h6>',
												'accesskey' => '',
												'classname'	 => 'btn_h6'
												),
							'anchor'	=> array(
												'tag_name'  => 'a',
												'tag_open'  => '<a href="[![Link:!:http://]!]"(!( title="[![Title]!]")!)>',
												'tag_close' => '</a>',
												'accesskey' => 'a',
												'classname'	 => 'btn_a'
												),
							'picture'	=> array(
												'tag_name'  => 'img',
												'tag_open'  => '<img src="[![Link:!:http://]!]" alt="[![Alternative text]!]" />',
												'tag_close' => '',
												'accesskey' => '',
												'classname'	 => 'btn_img'
												),
							// 'separator'	=> array(
							// 					'tag_name'  => 'separator',
							// 					'tag_open'  => '',
							// 					'tag_close' => '',
							// 					'accesskey' => '',
							// 					'classname'	 => 'markItUpSeparator'
							// 					),
							);


/* End of file html_buttons.php */
/* Location: ./system/expressionengine/config/html_buttons.php */