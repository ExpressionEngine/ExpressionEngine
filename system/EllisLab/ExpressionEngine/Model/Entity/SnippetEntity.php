<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class SnippetEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'snippets',
		'primary_key' => 'snippet_id', 
		'related_entities' => array(
			'site_id' => array(
				'entity' => 'SiteEntity',
				'key' => 'site_id'
			)
		)
	);
	

	// Properties	
	public $snippet_id;
	public $site_id;
	public $snippet_name;
	public $snippet_contents;

}
