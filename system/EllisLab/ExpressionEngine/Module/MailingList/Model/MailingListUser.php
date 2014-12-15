<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MailingListUser extends Model
{
	protected static $_primary_key = 'user_id';
	protected static $_gateway_names = array('MailingListUserGateway');

	protected static $_relationships = array(
		'MailingList' => array(
			'type' => 'many_to_one'
		)
	);

	protected $user_id;
	protected $list_id;
	protected $authcode;
	protected $email;
	protected $ip_address;

	public function getMailingList()
	{
		return $this->getRelated('MailingList');
	}

	public function setMailingList(MailingList $mailing_list)
	{
		return $this->setRelated('MailingList', $mailing_list);
	}
}
