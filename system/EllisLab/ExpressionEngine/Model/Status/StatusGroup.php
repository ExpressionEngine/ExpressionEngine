<?php

namespace EllisLab\ExpressionEngine\Model\Status;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Status Group Model
 *
 * @package		ExpressionEngine
 * @subpackage	Status
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class StatusGroup extends Model {

	protected static $_primary_key = 'group_id';
	protected static $_table_name = 'status_groups';
	protected static $_events = array('afterSave');

	protected static $_relationships = array(
		'Site' => array(
			'type'  => 'BelongsTo',
		),
		'Statuses' => array(
			'type'  => 'HasMany',
			'model' => 'Status'
		)
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;

	/**
	 * On save, make sure default statuses exist
	 */
	public function onAfterSave()
	{
		$open = $this->getFrontend()->make('Status');
		$open->group_id = $this->getId();
		$open->status_id = 1;
		$open->site_id = $this->site_id;
		$open->status = 'open';
		$open->status_order = 1;
		$open->save();

		$closed = $this->getFrontend()->make('Status');
		$closed->group_id = $this->getId();
		$closed->status_id = 2;
		$closed->site_id = $this->site_id;
		$closed->status = 'closed';
		$closed->status_order = 2;
		$closed->save();
	}
}