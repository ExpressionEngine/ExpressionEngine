<?php
namespace EllisLab\ExpressionEngine\Model\Template\Gateway;

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
 * ExpressionEngine Tempalte Group Table
 *
 * @package		ExpressionEngine
 * @subpackage	Template\Gateway
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TemplateGroupGateway extends RowDataGateway {

	protected static $_table_name 		= 'template_groups';
	protected static $_primary_key 		= 'group_id';
	protected static $_related_gateways	= array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'    => 'site_id'
		),
		'group_id' => array(
			'Templates' => array(
				'gateway' => 'TemplateGateway',
				'key'    => 'group_id'
			),
			'MemberGroups' => array(
				'gateway' => 'MemberGroupGateway',
				'key' => 'group_id',
				'pivot_table' => 'template_member_groups',
				'pivot_key' => 'template_group_id',
				'pivot_foreign_key' => 'group_id'
			)
		)
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $group_order;
	protected $is_site_default;

}
