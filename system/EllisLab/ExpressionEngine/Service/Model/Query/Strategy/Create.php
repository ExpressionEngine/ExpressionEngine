<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query\Strategy;

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
 * ExpressionEngine Model Query Save Strategy Class
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Create extends Update {

	/**
	 *
	 */
	public function run()
	{
		$data = $this->compileSet();

		$insert_ids = array();

		// todo unset primary key
		foreach ($data as $table => $data)
		{
			$this->db->set($data)->insert($table);
			$insert_ids[] = $this->db->last_insert_id();
		}

		return current($insert_ids);
	}
}