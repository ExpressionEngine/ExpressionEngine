<?php
namespace Ellislab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class CategoryEntity extends Entity {
	// Structural definition stuff
	protected $id_name = 'cat_id';
	protected $table_name = 'categories';
	protected $relations = array(
		'CategoryFieldDataEntity' 
			=> array('this.cat_id' => 'CategoryFieldDataEntity.cat_id'),
		'CategoryGroupEntity'
			=> array('this.group_id' => 'CategoryGroupEntity.group_id'),
		'SiteEntity' 
			=> array('this.site_id' => 'SiteEntity.site_id')
	);

	// Properties
	public $cat_id;
	public $site_id;
	public $group_id;
	public $parent_id;
	public $cat_name;
	public $cat_url_title;
	public $cat_description;
	public $cat_image;
	public $cat_order;
}
