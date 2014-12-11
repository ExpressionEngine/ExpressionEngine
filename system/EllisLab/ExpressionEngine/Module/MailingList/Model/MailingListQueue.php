<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MailingListQueue extends Model
{
	protected static $_primary_key = 'queue_id';
	protected static $_gateway_names = array('MailingListQueueGateway');
	protected static $_relationships = array(
		'MailingList' => array(
			'type' => 'many_to_one'
		)
	);

	protected $queue_id;
	protected $email;
	protected $list_id;
	protected $authcode;
	protected $date;

	public function getMailingList()
	{
		return $this->getRelated('MailingList');
	}

	public function setMailingList(MailingList $mailing_list)
	{
		return $this->setRelated('MailingList', $mailing_list);
	}
}
