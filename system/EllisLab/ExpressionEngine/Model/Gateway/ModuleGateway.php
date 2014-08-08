<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

/**
 *
 */
class ModuleGateway extends RowDataGateway {
	protected static $_table_name = 'modules';
	protected static $_primary_key = 'module_id';



	// Properties
	protected $module_id;
	protected $module_name;
	protected $module_version;
	protected $has_cp_backend;
	protected $has_publish_fields;

}
