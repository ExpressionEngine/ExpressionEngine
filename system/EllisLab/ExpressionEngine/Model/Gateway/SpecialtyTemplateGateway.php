<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

class SpecialtyTemplateGateway extends RowDataGateway
{
	protected static $_table_name = 'specialty_templates';
	protected static $_primary_key = 'template_id';
	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		)
	);


	public $template_id;
	public $site_id;
	public $enable_template;
	public $template_name;
	public $data_title;
	public $template_data;

}
