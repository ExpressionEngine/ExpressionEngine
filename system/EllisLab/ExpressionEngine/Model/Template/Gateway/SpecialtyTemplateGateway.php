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


	protected $template_id;
	protected $site_id;
	protected $enable_template;
	protected $template_name;
	protected $data_title;
	protected $template_data;

}
