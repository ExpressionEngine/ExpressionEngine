<?php

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamTrap extends Model {

	protected static $_table_name = 'spam_trap';
	protected static $_primary_key = 'trap_id';

	protected static $_typed_columns = array(
		'date' => 'timestamp'
	);

	protected static $_relationships = array(
		'Author' => array(
			'type'     => 'BelongsTo',
			'model'    => 'Member',
			'from_key' => 'author'
		),
	);

	protected $trap_id;
	protected $author;
	protected $ip_address;
	protected $date;
	protected $file;
	protected $class;
	protected $method;
	protected $data;
	protected $document;

}
