<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SnippetGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'snippets',
		'primary_key' => 'snippet_id', 
		'related_gateways' => array(
			'site_id' => array(
				'gateway' => 'SiteGateway',
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
