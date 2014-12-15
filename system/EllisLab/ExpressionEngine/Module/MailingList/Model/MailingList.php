<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MailingList extends Model
{
	protected static $_primary_key = 'list_id';
	protected static $_gateway_names = array('MailingListGateway');

	protected static $_relationships = array(
		'MailingListUser' => array(
			'type' => 'one_to_many',
		)
	);

	protected $list_id;
	protected $list_name;
	protected $list_title;
	protected $list_template;

	public function getMailingListUsers()
	{
		return $this->getRelationship('MailingListUser');
	}

	public function setMailingListUsers(array $users)
	{
		return $this->setRelationship('MailingListUser', $users);
	}


}
