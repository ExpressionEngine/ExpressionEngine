<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

$lang = array(
	'spam_module_name' => 'Spam',
	'spam_module_description' => 'Spam filter for comments and forum posts',
	'spam_settings' => 'SPAM Settings',
	'spam_sensitivity' => '%s%% Sensitivity',
	'spam_sensitivity_desc' => 'Percentage threshold at which content is marked as SPAM. Content will be marked as SPAM, if it has an equal to or higher chance of being SPAM.',
	'engine_training' => 'Engine Training',
	'spam_word_limit' => 'Word Limit',
	'spam_word_limit_desc' => 'Number of specific words to store, for training.
	Higher numbers can reduce performance speeds.',
	'spam_content_limit' => 'Content Limit',
	'spam_content_limit_desc' => 'Number of content entires to use, for training.<br>Higher numbers can reduce performance speeds.',
	'spam_content' => 'Content',
	'spam_type' => 'Type',
	'content_type' => 'content type',
	'forum_post' => 'forum post',
	'wiki_post' => 'wiki post',
	'comment' => 'comment'
);
