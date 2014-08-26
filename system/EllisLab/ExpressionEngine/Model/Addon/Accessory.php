<?php
namespace EllisLab\ExpressionEngine\Model\Addon;

use EllisLab\ExpressionEngine\Service\Model;

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
 * ExpressionEngine Accessory Model
 *
 * @package		ExpressionEngine
 * @subpackage	Addon
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Accessory extends Model {
	protected static $_primary_key = 'accessory_id';
	protected static $_gateway_names = array('AccessoryGateway');

	// Propeties
	protected $accessory_id;
	protected $class;
	protected $member_groups;
	protected $controllers;
	protected $accessory_version;

}
