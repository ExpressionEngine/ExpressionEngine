<?php
namespace EllisLab\ExpressionEngine\Model\Status\Gateway;

use EllisLab\ExpressionEngine\Service\Model\RowDataGateway;

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
 * ExpressionEngine Status Table
 *
 * @package		ExpressionEngine
 * @subpackage	Status\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class StatusGateway extends RowDataGateway {
	protected static $_table_name = 'statuses';
	protected static $_primary_key = 'status_id';
	protected static $_related_gateways = array(
		'group_id' => array(
			'gateway' => 'StatusGroupGateway',
			'key' => 'group_id'
		),
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		)
	);


	protected $status_id;
	protected $group_id;
	protected $site_id;
	protected $status;
	protected $status_order;
	protected $highlight;
}
