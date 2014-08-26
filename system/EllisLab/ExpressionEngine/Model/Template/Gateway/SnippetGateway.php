<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SnippetGateway extends RowDataGateway {
	protected static $_table_name = 'snippets';
	protected static $_primary_key = 'snippet_id';
	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		)
	);


	// Properties
	protected $snippet_id;
	protected $site_id;
	protected $snippet_name;
	protected $snippet_contents;

}
