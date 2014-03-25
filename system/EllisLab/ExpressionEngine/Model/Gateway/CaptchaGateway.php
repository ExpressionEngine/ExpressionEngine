<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class CaptchaGateway extends RowDataGateway {
	protected static $meta = array(
		'table_name' => 'captcha',
		'primary_key' => 'captcha_id'
	);
		

	// Properties
	public $captcha_id;
	public $date;
	public $ip_address;
	public $word;

}
