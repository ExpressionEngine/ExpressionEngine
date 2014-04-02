<?php
namespace EllisLab\ExpressionEngine\Module\Member\Model;

class ResetPassword extends Model
{
	protected static $_primary_key = 'reset_id';
	protected static $_gateway_names = array('ResetPasswordGateway');
	protected static $_key_map = array(
		'member_id' => 'ResetPasswordGateway',
		'reset_id' => 'ResetPasswordGateway'
	);


	protected $reset_id;
	protected $member_id;
	protected $resetcode;
	protected $date;

}
