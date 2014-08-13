<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model;

/**
 *
 */
class Template extends Model {

	// Meta data
	protected static $_primary_key = 'template_id';
	protected static $_gateway_names = array('TemplateGateway');

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one',
		),
		'TemplateGroup'	=> array(
			'type' => 'many_to_one'
		),
		'LastAuthor' => array(
			'type'	=> 'many_to_one',
			'model'	=> 'Member',
			'key'	=> 'last_author_id'
		)
	);

/*
//	protected $_lifecycle_events = array(
	protected $_bind_events = array(
		'delete', // fetch the whole object and fire onDelete
		'update'  // fetch the whole object and fire onUpdate
	);

	protected $_bind_events = array(
		'update',
		'delete'
	);
	// Batch size: 100

	// all events fire *before*
	protected $_bind_events = array(
		'delete.one',	// fetch objects one at a time
		'delete.all',	// [default] fetch everything being deleted and fire onDelete on all
		'update.batch'  // fetch the objects in batches of 100 and fire onUpdate
	);

	public function onBeforeUpdate($set)
	{
		$this->set($set);
		return FALSE;
	}
*/

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
		return $this->getRelated('TemplateGroup');
	}

	public function setTemplateGroup(TemplateGroup $template_group)
	{
		return $this->setRelated('TemplateGroup', $template_group);
	}

	public function getLastAuthor()
	{
		return $this->getRelated('LastAuthor');
	}

	public function setLastAuthor(Member $member)
	{
		return $this->setRelated('LastAuthor', $member);
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

