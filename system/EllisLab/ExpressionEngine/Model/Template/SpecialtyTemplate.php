<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model;

class SpecialtyTemplate extends Model
{
	// Meta data
	protected static $_primary_key = 'template_id';
	protected static $_gateway_names = array('SpecialtyTemplateGateway');
	protected static $_key_map = array(
		'template_id' => 'SpecialtyTemplateGateway',
		'site_id' => 'SpecialtyTemplateGateway'
	);

	// Properties
	public $template_id;
	public $site_id;
	public $enable_template;
	public $template_name;
	public $data_title;
	public $template_data;

	public function getSite()
	{
		return $this->manyToOne('Site', 'Site', 'site_id', 'site_id');
	}

	public function setSite(Site $site)
	{
		$this->setRelated('Site', $site);
		$this->site_id = $site->site_id;
		return $this;
	}

}
