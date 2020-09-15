<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Version_2_5_4;

/**
 * Update
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
		$steps = new \ProgressIterator(
			array(
				'_change_member_totals_length',
				'_update_session_table',
				'_update_security_hashes_table',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Changes column type for `total_entries` and `total_comments` in the
	 * members table from smallint to mediumint to match the columns in the
	 * channels table and stats table.
	 */
	private function _change_member_totals_length()
	{
		ee()->smartforge->modify_column(
			'members',
			array(
				'total_entries' => array(
					'name' => 'total_entries',
					'type' => 'mediumint(8)'
				),
				'total_comments' => array(
					'name' => 'total_comments',
					'type' => 'mediumint(8)'
				),
			)
		);
	}

	/**
	 * Clean up the menu and quicklink items so there's no XSS funny business
	 */
	private function _xss_clean_custom_links()
	{
		$members = ee()->db->select('member_id, quick_links, quick_tabs')
			->where('quick_links IS NOT NULL')
			->or_where('quick_tabs IS NOT NULL')
			->get('members')
			->result_array();

		// Sanitize quick_links and quick_tabs
		foreach ($members as $index => $data)
		{
			// Sanitize quick_links and quick_tabs
			$members[$index]['quick_links'] = $this->_sanitize_custom_links($data['quick_links']);
			$members[$index]['quick_tabs'] = $this->_sanitize_custom_links($data['quick_tabs']);
		}

		ee()->db->update_batch('members', $members, 'member_id');
	}

	/**
	 * Clean up custom links given a string containing multiple links broken up
	 * by newlines, with links broken up by pipes.
	 *
	 * This does not remove the [removed] replacements.
	 *
	 * @param  String $string String containing multiple custom links separated
	 *                        by newlines with links broken up by pipes
	 * @return String         Sanitized string containing custom links
	 */
	private function _sanitize_custom_links($string)
	{
		// Each string is comprised of multiple links broken up by newlines
		$lines = explode("\n", $string);

		foreach ($lines as $index => $line)
		{
			// Each link is three parts, the first being the name (which is
			// where we're concerned about XSS cleaning), the link, the order
			$links = explode('|', $line);
			$links[0] = ee('Security/XSS')->clean($links[0]);
			$lines[$index] = implode('|', $links);
		}

		return implode("\n", $lines);
	}

	/**
	 * update Session table
	 *
	 * @return TRUE
	 */
	private function _update_session_table()
	{
		ee()->smartforge->add_column(
			'sessions',
			array(
				'fingerprint' => array(
					'type'			=> 'varchar',
					'constraint'	=> 40
				),
				'sess_start' => array(
					'type'			=> 'int',
					'constraint'	=> 10,
					'unsigned'		=> TRUE,
					'default'		=> 0,
					'null'			=> FALSE
				)
			),
			'user_agent'
		);

		return TRUE;
	}

	/**
	 * Update the security_hashes table to convert ip_address to session_id
	 */
	private function _update_security_hashes_table()
	{
		ee()->smartforge->modify_column(
			'security_hashes',
			array(
				'ip_address' => array(
					'name' 			=> 'session_id',
					'type' 			=> 'varchar',
					'constraint' 	=> 40
				)
			)
		);

		ee()->db->truncate('security_hashes');

		return TRUE;
	}
}
/* END CLASS */

// EOF
