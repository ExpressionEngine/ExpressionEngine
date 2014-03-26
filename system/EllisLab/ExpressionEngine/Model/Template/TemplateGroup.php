<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model as Model;

class TemplateGroup extends Model {

	protected static $_primary_key	= 'group_id';
	protected static $_gateway_names = array('TemplateGroupGateway');
	protected static $_key_map		= array(
		'group_id' => 'TemplateGroupGateway',
		'site_id' => 'TemplateGroupGateway'
	);
	protected static $_cascade = 'Templates';

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
		return $this->oneToMany('Templates', 'Template', 'group_id', 'group_id');
	}

	public function setTemplates(array $templates)
	{
		$this->setRelated('Templates', $templates);
		foreach($templates as $template)
		{
			$template->group_id = $this->group_id;
		}
		return $this;
	}

	public function getMemberGroups()
	{
		return $this->manyToMany('MemberGroup', 'template_group_id', 'group_id', 'MemberGroups');
	}

	public function setMemberGroups(array $member_groups)
	{
		$this->setRelated('MemberGroups', $member_groups);
		return $this;
	}

	public function getSite()
	{
		return $this->manyToOne('Site', 'Site', 'site_id', 'site_id');
	}

	public function setSite(Site $site)
	{
		$this->setRelated('Site', $site);
		$this->site_id = $site->site_id;
		return $this;
	}

}
