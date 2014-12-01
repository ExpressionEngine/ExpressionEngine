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
}
