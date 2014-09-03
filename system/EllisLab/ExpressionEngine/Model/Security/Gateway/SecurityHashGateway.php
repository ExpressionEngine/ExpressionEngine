<?php
namespace EllisLab\ExpressionEngine\Model\Security\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway\RowDataGateway;

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
 * ExpressionEngine CSRF Hash Table
 *
 * @package		ExpressionEngine
 * @subpackage	Security\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class SecurityHashGateway extends RowDataGateway {
	protected static $_table_name = 'security_hashes';
	protected static $_primary_key = 'hash_id';

	protected static $_related_gateways = array(
		'session_id' => array(
			'gateway' => 'SessionGateway',
			'key' => 'session_id'
		)
	);

	// Properties
	protected $hash_id;
	protected $date;
	protected $session_id;
	protected $hash;
	protected $used;

}
