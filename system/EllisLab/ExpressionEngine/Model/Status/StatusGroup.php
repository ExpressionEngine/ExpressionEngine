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
	protected static $_gateway_names = array('StatusGroupGateway');
	protected static $_key_map = array(
		'group_id' => 'StatusGroupGateway',
		'site_id' => 'StatusGroupGateway'
	);

	protected static $_relationships = array(
		'Statuses' => array(
			'type' => 'one_to_many',
			'model' => 'Status'
		)
	);

	public function getStatuses()
	{
		return $this->getRelated('Statuses');
	}

	public function setStatuses(array $statuses)
	{
		return $this->setRelated('Statuses', $statuses);
	}

	protected $group_id;
	protected $site_id;
	protected $group_name;
}
