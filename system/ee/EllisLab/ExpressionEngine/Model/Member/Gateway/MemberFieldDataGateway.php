<?php

namespace EllisLab\ExpressionEngine\Model\Member\Gateway;

use EllisLab\ExpressionEngine\Model\Content\VariableColumnGateway;

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
