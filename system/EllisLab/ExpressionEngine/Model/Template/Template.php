<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model;

/**
 *
 */
class Template extends Model {

	protected static $_meta = array(
		'primary_key'	=> 'template_id',
		'gateway_names'	=> array('TemplateGateway'),
		'key_map'		=> array(
			'template_id' => 'TemplateGateway',
			'group_id'    => 'TemplateGateway',
			'last_author_id' => 'TemplateGateway',
			'site_id' => 'TemplateGateway'
		)
	);

	// Properties
	protected $template_id;
	protected $site_id;
	protected $group_id;
	protected $template_name;
	protected $save_template_file;
	protected $template_type;
	protected $template_data;
	protected $template_notes;
	protected $edit_date;
	protected $last_author_id;
	protected $cache;
	protected $refresh;
	protected $no_auth_bounce;
	protected $enable_http_auth;
	protected $allow_php;
	protected $php_parse_location;
	protected $hits;

	/**
	 *
	 */
	public function getTemplateGroup()
	{
		return $this->manyToOne('TemplateGroup', 'TemplateGroup', 'group_id', 'group_id');
	}

	public function setTemplateGroup(TemplateGroup $template_group)
	{
		$this->setRelated('TemplateGroup', $template_group);
		$this->group_id = $template_group->group_id;
		return $this;
	}

	public function getLastAuthor()
	{
		return $this->manyToOne('LastAuthor', 'Member', 'last_author_id', 'member_id');
	}

	public function setLastAuthor(Member $member)
	{
		$this->setRelated('LastAuthor', $member);	
		$this->last_author_id = $member->member_id;
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

