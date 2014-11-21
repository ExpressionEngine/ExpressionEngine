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
 * ExpressionEngine Template Model
 *
 * A model representing a template.  Templates contain a mix of EECode and HTML
 * and are parsed to become the front end pages of sites built with
 * ExpressionEngine.
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Template extends Model {

	protected static $_primary_key = 'template_id';
	protected static $_gateway_names = array('TemplateGateway');

	protected static $_relationships = array(
		'TemplateGroup' => array(
			'type' => 'BelongsTo'
		)
	);
/*
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
		),
		'NoAccess' => array(
			'type' => 'many_to_many',
			'model' => 'MemberGroup'
		)
	);
*/
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
/*
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

	public function getNoAccess()
	{
		return $this->getRelated('NoAccess');
	}

	public function setNoAccess($no_access)
	{
		return $this->setRelated('NoAccess', $no_access);
	}
	*/
}

