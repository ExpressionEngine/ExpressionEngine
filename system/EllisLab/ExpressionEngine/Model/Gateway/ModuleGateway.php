<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

/**
 *
 */
class ModuleGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'modules',
		'primary_key' => 'module_id'
	);	



	// Properties
	public $module_id;
	public $module_name;
	public $module_version;
	public $has_cp_backend;
	public $has_publish_fields;

}
