<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Member\Gateway;

use EllisLab\ExpressionEngine\Model\Content\VariableColumnGateway;

/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

/**
 * ExpressionEngine Member Field Data Table
 *
 * @package		ExpressionEngine
 * @subpackage	Category\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class MemberFieldDataGateway extends VariableColumnGateway {

	protected static $_table_name = 'member_data';
	protected static $_primary_key = 'member_id';

	protected static $_related_gateways = array(
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key'	 => 'member_id'
		)
	);

	// Properties
	protected $member_id;

}

// EOF
