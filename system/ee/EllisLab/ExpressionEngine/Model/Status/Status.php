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
 * ExpressionEngine Status Model
 *
 * @package		ExpressionEngine
 * @subpackage	Status
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Status extends Model {

	protected static $_primary_key = 'status_id';
	protected static $_table_name = 'statuses';

	protected static $_typed_columns = array(
		'site_id'         => 'int',
		'group_id'        => 'int',
		'status_order'    => 'int'
	);

	protected static $_relationships = array(
		'StatusGroup' => array(
			'type' => 'BelongsTo'
		),
		'Site' => array(
			'type' => 'BelongsTo'
		),
		'NoAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'status_no_access',
				'left' => 'status_id',
				'right' => 'member_group'
			)
		)
	);

	protected static $_events = array(
		'beforeInsert'
	);

	protected $status_id;
	protected $site_id;
	protected $group_id;
	protected $status;
	protected $status_order;
	protected $highlight;

	/**
	 * New statuses get appended
	 */
	public function onBeforeInsert()
	{
		$status_order = $this->getProperty('status_order');

		if (empty($status_order))
		{
			$count = $this->getFrontend()->get('Status')
				->filter('group_id', $this->getProperty('group_id'))
				->count();
			$this->setProperty('status_order', $count + 1);
		}
	}
}

// EOF
