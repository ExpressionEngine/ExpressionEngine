<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MailingListUser extends Model
{
	protected static $_primary_key = 'user_id';
	protected static $_table_name = 'mailing_list';

	protected static $_relationships = array(
		'MailingList' => array(
			'type' => 'belongsTo'
		)
	);

	protected $user_id;
	protected $list_id;
	protected $authcode;
	protected $email;
	protected $ip_address;

}
