<?php
namespace EllisLab\ExpressionEngine\Model\Gateway;

use EllisLab\ExpressionEngine\Model\Gateway\RowDataGateway;

class CaptchaGateway extends RowDataGateway {
	protected static $_table_name = 'captcha';
	protected static $_primary_key = 'captcha_id';


	// Properties
	protected $captcha_id;
	protected $date;
	protected $ip_address;
	protected $word;

}
