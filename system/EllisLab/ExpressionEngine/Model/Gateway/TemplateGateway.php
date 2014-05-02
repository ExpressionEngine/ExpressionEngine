<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

class TemplateGateway extends RowDataGateway {

	protected static $_table_name 		= 'templates';
	protected static $_primary_key 		= 'template_id';
	protected static $_related_gateways	= array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'group_id' => array(
			'gateway' => 'TemplateGroupGateway',
			'key'    => 'group_id'
		),
		'last_author_id' => array(
			'gateway' => 'MemberGateway',
			'key'	 => 'member_id'
		),
	);
	protected static $_validation_rules = array(
		'template_id' => 'required|isNatural',
		'site_id' => 'required|isNatural',
		'group_id' => 'required|isNatural',
	'template_name' => 'required|alphaDash'
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
