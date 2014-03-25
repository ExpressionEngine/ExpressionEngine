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
	public $variable_id;
	public $site_id;
	public $variable_name;
	public $variable_data;

}
