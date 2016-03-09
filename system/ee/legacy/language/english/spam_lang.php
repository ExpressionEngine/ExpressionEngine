<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */

$lang = array(
	'spam_module_name' => 'Spam',
	'spam_module_description' => 'Spam filter for comments and forum posts',
	'spam_settings' => 'SPAM Settings',
	'spam_sensitivity' => 'Sensitivity',
	'spam_sensitivity_desc' => 'Percentage threshold at which content is marked as SPAM. Content will be marked as SPAM, if it has an equal to or higher chance of being SPAM.',
	'engine_training' => 'Engine Training',
	'spam_word_limit' => 'Word Limit',
	'spam_word_limit_desc' => 'Number of specific words to store, for training.
	Higher numbers can reduce performance speeds.',
	'spam_content_limit' => 'Content Limit',
	'spam_content_limit_desc' => 'Number of content entries to use, for training.<br>Higher numbers can reduce performance speeds.',
	'spam_content' => 'Content',
	'spam_type' => 'Type',
	'content_type' => 'content type',
	'forum_post' => 'forum post',
	'wiki_post' => 'wiki post',
	'comment' => 'comment',
	'email' => 'email',
	'spam' => 'SPAM',
	'all_spam' => 'All SPAM',
	'search_spam' => 'Search Spam',
	'approve_spam' => 'NOT SPAM, then approve',
	'deny_spam' => 'SPAM, then remove from spam trap',
	'mark_selected' => 'mark selected',
	'show_all_spam' => 'show all spam',
	'spam_trap_removed' => '<b>%s</b> items in the Spam Trap have been removed and marked as spam',
	'spam_trap_approved' => '<b>%s</b> items in the Spam Trap have been approved and marked as ham',
	'spam_settings_updated' => 'Your Spam settings have been saved',
	'success' => 'Success',
	'update_training' => 'Update Training',
	'update_training_desc' => 'This will download up-to-date training data from EllisLab and update your database.',
	'training_downloaded' => 'Training data downloaded',
	'training_prepared' => 'Training data prepared',
	'updating_vocabulary' => 'Updating Vocabulary',
	'updating_parameters' => 'Updating Parameters',
	'training_finished' => 'Training Finished',
	'training_update_failed' => 'Could not update training data'
);

// EOF
