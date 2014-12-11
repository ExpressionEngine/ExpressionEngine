<?php

namespace EllisLab\ExpressionEngine\Module\Member\Model\Gateway;

use EllisLab\ExpressionEngine\Service\Model\Gateway;

class MemberSearchGateway extends Gateway {

	protected static $_table_name = 'member_search';
	protected static $_primary_key = 'search_id';

	protected static $_related_gateways = array(
		'site_id' => array(
			'gateway' => 'SiteGateway',
			'key' => 'site_id'
		),
		'member_id' => array(
			'gateway' => 'MemberGateway',
			'key' => 'member_id'
		)
	);

	public $search_id;
	public $site_id;
	public $search_date;
	public $keywords;
	public $fields;
	public $member_id;
	public $ip_address;
	public $total_results;
	public $query;

}
