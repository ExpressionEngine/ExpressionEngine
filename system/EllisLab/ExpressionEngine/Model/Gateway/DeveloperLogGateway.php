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


	protected $log_id;
	protected $timestamp;
	protected $viewed;
	protected $description;
	protected $function;
	protected $line;
	protected $file;
	protected $deprecated_since;
	protected $use_instead;
	protected $template_id;
	protected $template_name;
	protected $template_group;
	protected $addon_module;
	protected $addon_method;
	protected $snippets;
	protected $hash;

}
