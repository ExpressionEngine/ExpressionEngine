<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_update_email_cache_table',
				'_insert_comment_settings_into_db',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	// -------------------------------------------------------------------------

	/**
	 * Removes 3 columns and adds 1 column to the email_cache table
	 *
	 * @access private
	 * @return void
	 */
	private function _update_email_cache_table()
	{
		ee()->smartforge->drop_column('email_cache', 'mailinglist');
		ee()->smartforge->drop_column('email_cache', 'priority');

		ee()->smartforge->add_column(
			'email_cache',
			array(
				'attachments' => array(
					'type'			=> 'mediumtext',
					'null'			=> TRUE
				)
			)
		);
	}

	// -------------------------------------------------------------------------

	/**
	 * Previously, Comment module settings were stored in config.php. Since the
	 * Comment module is more integrated like Channel, let's take the settings
	 * out of there and put them in the sites table because it's a better place
	 * for them and they can be separated by site.
	 *
	 * @access private
	 * @return void
	 */
	private function _insert_comment_settings_into_db()
	{
		$comment_edit_time_limit = ee()->config->item('comment_edit_time_limit');
		
		$settings = array(
			// This is a new config, default it to y if not set
			'enable_comments' => ee()->config->item('enable_comments') ?: 'y',
			// These next two default to n
			'comment_word_censoring' => (ee()->config->item('comment_word_censoring') == 'y') ? 'y' : 'n',
			'comment_moderation_override' => (ee()->config->item('comment_moderation_override') == 'y') ? 'y' : 'n',
			// Default this to 0
			'comment_edit_time_limit' => ($comment_edit_time_limit && ctype_digit($comment_edit_time_limit))
				? $comment_edit_time_limit : 0
		);

		ee()->config->update_site_prefs($settings, 'all');
		ee()->config->_update_config('', $settings);
	}

}
/* END CLASS */

/* End of file ud_300.php */
/* Location: ./system/expressionengine/installer/updates/ud_300.php */
