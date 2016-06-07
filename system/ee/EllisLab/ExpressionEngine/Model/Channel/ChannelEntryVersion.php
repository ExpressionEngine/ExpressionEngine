<?php

namespace EllisLab\ExpressionEngine\Model\Channel;

use EllisLab\ExpressionEngine\Service\Model\Model as Model;

class ChannelEntryVersion extends Model {

	protected static $_primary_key = 'version_id';
	protected static $_table_name = 'entry_versioning';

	protected static $_typed_columns = array(
		'entry_id'     => 'int',
		'channel_id'   => 'int',
		'author_id'    => 'int',
		'version_date' => 'timestamp',
		'version_data' => 'serialized',
	);

	protected static $_relationships = array(
		'ChannelEntry' => array(
			'type' => 'belongsTo',
		),
		'Author'	=> array(
			'type' => 'belongsTo',
			'model' => 'Member',
			'from_key' 	=> 'author_id'
		),
	);

	protected static $_validation_rules = array(
		'entry_id'     => 'required',
		'channel_id'   => 'required',
		'author_id'    => 'required',
		'version_date' => 'required',
		'version_data' => 'required',
	);

	protected $version_id;
	protected $entry_id;
	protected $channel_id;
	protected $author_id;
	protected $version_date;
	protected $version_data;

	public function getAuthorName()
	{
		return ($this->author_id && $this->Author) ? $this->Author->getMemberName() : '';
	}
}

// EOF
