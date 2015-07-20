<?php

namespace EllisLab\ExpressionEngine\Module\MailingList\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class MailingList extends Model
{
	protected static $_primary_key = 'list_id';
	protected static $_table_name = 'mailing_lists';

	protected static $_relationships = array(
		'MailingListUser' => array(
			'type' => 'hasMany'
		),
		'MailingListQueue' => array(
			'type' => 'hasMany'
		)
	);

	protected $list_id;
	protected $list_name;
	protected $list_title;
	protected $list_template;

}
