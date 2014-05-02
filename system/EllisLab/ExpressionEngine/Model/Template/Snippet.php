<?php
namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Model\Model;

class Snippet extends Model {

	// Meta data
	protected static $_primary_key = 'snippet_id';
	protected static $_gateway_names = array('SnippetGateway');
	protected static $_key_map = array(
		'snippet_id' => 'SnippetGateway',
		'site_id' => 'SnippetGateway'
	);

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one'
		)
	);


	// Properties
	public $snippet_id;
	public $site_id;
	public $snippet_name;
	public $snippet_contents;

	public function getSite()
	{
		return $this->getRelated('Site');
	}

	public function setSite(Site $site)
	{
		return $this->setRelated('Site', $site);
	}
}
