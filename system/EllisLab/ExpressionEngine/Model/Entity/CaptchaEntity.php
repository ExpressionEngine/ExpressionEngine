<?php
namespace EllisLab\ExpressionEngine\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class CaptchaEntity extends Entity {
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
