<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model;

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
	protected static $_cascade = 'Templates';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one'
		),
		'Templates' => array(
			'type' => 'one_to_many',
			'model' => 'Template'
		),
		'MemberGroups' => array(
			'type' => 'many_to_many',
			'model' => 'MemberGroup',
		)
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $group_order;
	protected $is_site_default;

	/**
	 *
	 */
	public function getTemplates()
	{
		return $this->getRelated('Templates');
	}

	public function setTemplates($templates)
	{
		return $this->setRelated('Templates', $templates);
	}

	public function getMemberGroups()
	{
		return $this->getRelated('MemberGroups');
	}

	public function setMemberGroups($member_groups)
	{
		return $this->setRelated('MemberGroups', $member_groups);
	}

	public function getSite()
	{
		return $this->getRelated('Site');
	}

	public function setSite(Site $site)
	{
		return $this->setRelated('Site', $site);
	}

}
