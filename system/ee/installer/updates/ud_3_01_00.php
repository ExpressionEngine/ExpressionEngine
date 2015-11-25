<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.1.0
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

	public $version_suffix = '';
	public $errors = array();

	/**
	 * Do Update
	 *
	 * @return bool Success?
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'move_avatars',
				'update_member_data_column_names',
			)
		);

		foreach ($steps as $k => $v)
		{
			try
			{
				$this->$v();
			}
			catch (Exception $e)
			{
				$this->errors[] = $e->getMessage();
			}
		}

		return empty($this->errors);
	}

	/**
	 * Fields created in 3.0 were missing the 'm_' prefix on their data columns,
	 * so we need to add the prefix back
	 */
	private function update_member_data_column_names()
	{
		$member_data_columns = ee()->db->list_fields('member_data');

		$columns_to_modify = array();
		foreach ($member_data_columns as $column)
		{
			if ($column == 'member_id' OR 						// Don't rename the primary key
				substr($column, 0, 2) == 'm_' OR 				// or if it already has the prefix
				in_array('m_'.$column, $member_data_columns)) 	// or if the prefixed column already exists (?!)
			{
				continue;
			}

			$columns_to_modify[$column] = array(
				'name' => 'm_'.$column,
				'type' => (strpos($column, 'field_ft_') !== FALSE) ? 'tinytext' : 'text'
			);
		}

		ee()->smartforge->modify_column('member_data', $columns_to_modify);
	}

	/**
 	* Move the default avatars into a subdirectory
 	* @return void
 	*/
	private function move_avatars()
	{
		$avatar_path = realpath(ee()->config->item('avatar_path'));
		$avatar_path_clean = htmlentities($avatar_path);

		// Does the path exist?
		if (empty($avatar_path))
		{
			throw new UpdaterException_3_1_0('<kbd>avatar_path</kbd> is not defined.');
		}

		// Check that we haven't already done this
		if (file_exists($avatar_path.'/default/'))
		{
			return TRUE;
		}

		if ( ! file_exists($avatar_path))
		{
			throw new UpdaterException_3_1_0("<kbd>{$avatar_path_clean}</kbd> is not a valid path.");
		}

		// Check to see if the directory is writable
		if ( ! is_writable($avatar_path))
		{
			if ( ! @chmod($avatar_path, DIR_WRITE_MODE))
			{
				throw new UpdaterException_3_1_0("<kbd>{$avatar_path_clean}</kbd> is not writeable.");
			}
		}

		// Create the default directory
		if ( ! mkdir($avatar_path.'/default/', DIR_WRITE_MODE))
		{
			throw new UpdaterException_3_1_0("Could not create <kbd>{$avatar_path_clean}/default/</kbd>.");
		}

		// Copy over the index.html
		if ( ! copy($avatar_path.'/index.html', $avatar_path.'/default/index.html'))
		{
			throw new UpdaterException_3_1_0("Could not copy <kbd>index.html</kbd> to <kbd>{$avatar_path_clean}/default/</kbd>.");
		}

		$default_avatars = array(
			'avatar_tree_hugger_color.png',
			'bad_fur_day.jpg',
			'big_horns.jpg',
			'eat_it_up.jpg',
			'ee_paint.jpg',
			'expression_radar.jpg',
			'flying_high.jpg',
			'hair.png',
			'hanging_out.jpg',
			'hello_prey.jpg',
			'light_blur.jpg',
			'ninjagirl.png',
			'procotopus.png',
			'sneak_squirrel.jpg',
			'zombie_bunny.png'
		);
		foreach ($default_avatars as $filename)
		{
			if ( ! rename($avatar_path.'/'.$filename, $avatar_path.'/default/'.$filename))
			{
				throw new UpdaterException_3_1_0("Could not copy default avatars to <kbd>{$avatar_path_clean}/default/</kbd>");
			}
		}
	}
}

class UpdaterException_3_1_0 extends Exception
{
	function __construct($message)
	{
		parent::__construct($message.' <a href="https://ellislab.com/expressionengine/user-guide/installation/version_notes_3.1.0.html">Please see 3.1.0 version notes.</a>');
	}
}
