<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;


class DeveloperLogGateway extends RowDataGateway {
	protected static $_table_name = 'developer_log';
	protected static $_primary_key = 'log_id';
	protected static $_related_entites = array(
		'template_id' => array(
			'gateway' => 'TemplateGateway',
			'key' => 'template_id'
		),
	);


	public $log_id;
	public $timestamp;
	public $viewed;
	public $description;
	public $function;
	public $line;
	public $file;
	public $deprecated_since;
	public $use_instead;
	public $template_id;
	public $template_name;
	public $template_group;
	public $addon_module;
	public $addon_method;
	public $snippets;
	public $hash;

}
