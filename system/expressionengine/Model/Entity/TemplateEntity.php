<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class TemplateEntity extends Entity {

	protected static $meta = array(
		'table_name' 		=> 'templates',
		'primary_key' 		=> 'template_id',
		'related_entities' 	=> array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key'    => 'site_id'
			),
			'group_id' => array(
				'entity' => 'TemplateGroupEntity',
				'key'    => 'group_id'
			)
		)
	);


	// Properties
	public $template_id;
	public $site_id;
	public $group_id;
	public $template_name;
	public $save_template_file;
	public $template_type;
	public $template_data;
	public $template_notes;
	public $edit_date;
	public $last_author_id;
	public $cache;
	public $refresh;
	public $no_auth_bounce;
	public $enable_http_auth;
	public $allow_php;
	public $php_parse_location;
	public $hits;


}
