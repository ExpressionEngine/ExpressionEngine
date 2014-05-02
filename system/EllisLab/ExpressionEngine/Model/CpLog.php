<?php
namespace EllisLab\ExpressionEngine\Model;

class CpLog extends Model {
	// Meta data
	protected static $_primary_key = 'id';
	protected static $_gateway_names = array('CpLogGateway');

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
	protected $username;
	protected $ip_address;
	protected $act_date;
	protected $action;
}
