<?php

namespace EllisLab\ExpressionEngine\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamTrap extends Model {

	protected static $_primary_key = 'trap_id';

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
