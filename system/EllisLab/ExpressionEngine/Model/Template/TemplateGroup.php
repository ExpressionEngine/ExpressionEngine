<?php
namespace EllisLab\ExpressionEngine\Model\Template;

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
 * ExpressionEngine Template Group Model
 *
 * @package		ExpressionEngine
 * @subpackage	TemplateGroup
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TemplateGroup extends Model {

	protected static $_primary_key = 'group_id';
	protected static $_gateway_names = array('TemplateGroupGateway');

	protected static $_relationships = array(
		'MemberGroups' => array(
			'type'     => 'HasAndBelongsToMany',
			'model'    => 'MemberGroup',
			'from_key' => 'group_id',
			'pivot' => array(
				'table' => 'template_member_groups',
				'left'  => 'template_gorup_id',
				'right' => 'group_id'
			)
		),
		'Templates' => array(
			'type' => 'HasMany',
			'model' => 'Template'
		),
		'Site' => array(
			'type' => 'BelongsTo'
		)
	);

	protected static $_validation_rules = array(
		'is_site_default' => 'enum[y|n]',
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $group_order;
	protected $is_site_default;

}