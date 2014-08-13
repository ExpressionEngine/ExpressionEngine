<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

class GlobalVariableGateway extends RowDataGateway
{
	// Meta Data
	protected static $_table_name 		= 'global_variables';
	protected static $_primary_key 		= 'variable_id';
	protected static $_related_gateways	= array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		)
	);

	// Properties
	protected $variable_id;
	protected $site_id;
	protected $variable_name;
	protected $variable_data;

}
