<?php
namespace EllisLab\ExpressionEngine\Module\Search\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class SearchLogGateway extends RowDataGateway {
	protected static $_table_name 		= 'search_log';
	protected static $_primary_key 		= 'id';
	protected static $_related_gateways	= array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key'	 => 'site_id'
		),
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key'	 => 'member_id'
		),
	);

	public $id;
	public $site_id;
	public $member_id;
	public $screen_name;
	public $ip_address;
	public $search_date;
	public $search_type;
	public $search_terms;
}
