<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model\Entity;

use EllisLab\ExpressionEngine\Model\Entity\Entity as Entity;

class ChannelDataEntity extends Entity {
	protected static $meta = array(
		'table_name' => 'channel_data',
		'primary_key' => 'entry_id',
	);

	public $entry_id;
	public $channel_id;
	public $site_id;
	
	protected $fields = array();

	public function __get($name)
	{
		if (strpos($name, 'field_id_') === 0)
		{
			list($id) = sscanf($name, 'field_id_%d');
			return $this->fields[$id]->data;
		}
		elseif(strpos($name, 'field_fmt_') === 0)
		{
			list($id) = sscanf($name, 'field_fmt_%d');
			return $this->fields[$id]->format;
		}
	}

	public function __set($name, $value)
	{
		if (strpos($name, 'field_id_') === 0)
		{
			list($id) = sscanf($name, 'field_id_%d');
			if ( ! isset($this->fields[$id]))
			{
				$this->fields[$id] = new ChannelFieldData($id);
			}
			$this->fields[$id]->data = $value;
		}
		elseif(strpos($name, 'field_fmt_') === 0)
		{
			list($id) = sscanf($name, 'field_fmt_%d');
			if ( ! isset($this->fields[$id]))
			{
				$this->fields[$id] = new ChannelFieldData($id);
			}
			$this->fields[$id]->format = $value;
		}
	}

	public function save()
	{

	}

}

class ChannelFieldData {
	public $field_id;
	public $data;
	public $format;

	public function __construct($id)
	{
		$this->field_id = $id;
	}
}


