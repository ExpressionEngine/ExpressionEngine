<?php
namespace EllisLab\ExpressionEngine\Module\Search\Model;

use EllisLab\ExpressionEngione\Model\Model;

class SearchLog extends Model {
	// Meta data
	protected static $_primary_key = 'id';
	protected static $_gateway_names = array('SearchLogGateway');

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'many_to_one'
		),
		'Member'	=> array(
			'type' => 'many_to_many'
		)
	);

	protected $id;
	protected $site_id;
	protected $member_id;
	protected $screen_name;
	protected $ip_address;
	protected $search_date;
	protected $search_type;
	protected $search_terms;
}
