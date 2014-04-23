<?php
namespace EllisLab\ExpressionEngine\Model;

class DeveloperLog extends Model {

	protected static $_primary_key = 'log_id';
	protected static $_gateway_names = array('DeveloperLogGateway');

	protected static $_relationships = array(
		'Template' => array(
			'type' => 'many_to_one'
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
