<?php

namespace EllisLab\ExpressionEngine\Model\Status;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * ExpressionEngine Status Group Model
 *
 * @package		ExpressionEngine
 * @subpackage	Status
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class StatusGroup extends Model {

	protected static $_primary_key = 'group_id';
	protected static $_table_name = 'status_groups';
	protected static $_events = array('afterInsert');

	protected static $_relationships = array(
		'Site' => array(
			'type'  => 'BelongsTo',
		),
		'Channel' => array(
			'weak'	=> TRUE,
			'type'  => 'HasMany',
			'to_key' => 'status_group'
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
	public function onAfterInsert()
	{
		$open = $this->getFrontend()->make('Status');
		$open->group_id = $this->getId();
		$open->site_id = $this->site_id;
		$open->status = 'open';
		$open->status_order = 1;
		$open->highlight = '009933';
		$open->save();

		$closed = $this->getFrontend()->make('Status');
		$closed->group_id = $this->getId();
		$closed->site_id = $this->site_id;
		$closed->status = 'closed';
		$closed->status_order = 2;
		$closed->highlight = '990000';
		$closed->save();
	}
}

// EOF
