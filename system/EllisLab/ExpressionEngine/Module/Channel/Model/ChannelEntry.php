<?php

namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Library\Data\Collection;
use EllisLab\ExpressionEngine\Model\FieldDataContentModel;

/**
 * Channel Entry
 *
 * An entry in a content channel.  May have multiple custom fields in
 * addition to a number of built in fields.  Is content and may be
 * rendered on the front end.  Has a publish form that includes its
 * many fields as sub publish elements.
 *
 * Related to Channel which defines the structure of this content.
 */
class ChannelEntry extends FieldDataContentModel {

	protected static $_primary_key = 'entry_id';
	protected static $_gateway_names = array('ChannelTitleGateway', 'ChannelDataGateway');

	protected static $_field_content_class = 'ChannelFieldContent';
	protected static $_field_content_gateway = 'ChannelDataGateway';

	protected static $_relationships = array(
		'Channel' => array(
			'type' => 'belongsTo',
			'key' => 'channel_id'
		),
		'Author'	=> array(
			'type' => 'belongsTo',
			'model' => 'Member',
			'from_key' 	=> 'author_id'
		),
		'Categories' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Category',
			'pivot' => array(
				'table' => 'categories'
			)
		)
	);

	// Properties
	protected $entry_id;
	protected $site_id;
	protected $channel_id;
	protected $author_id;
	protected $forum_topic_id;
	protected $ip_address;
	protected $title;
	protected $url_title;
	protected $status;
	protected $versioning_enabled;
	protected $view_count_one;
	protected $view_count_two;
	protected $view_count_three;
	protected $view_count_four;
	protected $allow_comments;
	protected $sticky;
	protected $entry_date;
	protected $year;
	protected $month;
	protected $day;
	protected $expiration_date;
	protected $comment_expiration_date;
	protected $edit_date;
	protected $recent_comment_date;
	protected $comment_total;

	protected $_fields;

	protected function getFieldTypeInfo()
	{
		// todo cache/move/ugly!
		$db = clone ee()->db;
		$db->_reset_select();
		$db->from('channel_fields');

		return new Collection($db->get()->result_array());
	}

	public function fill($data)
	{
		parent::fill($data);

		$fields = array();
		$field_types = $this->getFieldTypeInfo()->indexBy('field_id');

		foreach ($data as $key => $value)
		{
			if (preg_match('/^field_id_(\d+)$/', $key, $matches))
			{
				$id = $matches[1];

				$field = new Field($id, $field_types[$id]);
				$field->setData($value);
				$field->setContentId($this->getId());

				if (isset($data['field_ft_'.$id]))
				{
					$field->setFormat($data['field_ft_'.$id]);
				}

				$fields[] = $field;
			}
		}

		$this->_fields = new Collection($fields);
	}

	public function getForm()
	{
		return $this->_fields->getForm();
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getContentStructure()
	{
		return $this->getChannel();
	}

	/**
	 * Renders the piece of content for the front end, parses the tag data
	 * called by the module when rendering tagdata.
	 *
	 * @param	ParsedTemplate|string	$template	The parsed template from
	 * 						the template engine or a string of tagdata.
	 *
	 * @return	Template|string	The parsed template with relevant tags replaced
	 *							or the tagdata string with relevant tags replaced.
	 */
	public function render($template)
	{
	}
}

class Field {

	private $id;
	private $data;
	private $format;
	private $type_info;
	private $field_name;
	private $content_id;

	public function __construct($field_id, $type_info)
	{
		$this->id = $field_id;
		$this->type_info = $type_info;
		$this->field_name = 'field_id_'.$field_id;
	}

	public function getName()
	{
		return $this->field_name;
	}

	public function setContentId($id)
	{
		$this->content_id = $id;
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function setFormat($format)
	{
		$this->format = $format;
	}

	public function getForm()
	{
		$this->setupField();

		$field_output = array();

		$data = $this->setupField();

		if (isset($data['string_override']))
		{
			return $data['string_override'];
		}

		ee()->api_channel_fields->setup_handler($data['field_id']);
		ee()->api_channel_fields->apply('_init', array(array(
			'content_id' => $this->content_id
		)));

		$field_value = set_value($this->getName(), $data['field_data']);

		return ee()->api_channel_fields->apply('display_publish_field', array($field_value));
	}

	protected function setupField()
	{
		$field_dt = ''; // todo!
		$field_fmt = $this->format;
		$field_data = $this->data;
		$field_name = $this->getName();

		$info = $this->type_info;

		$settings = array(
			'field_instructions'	=> trim($info['field_instructions']),
			'field_text_direction'	=> ($info['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
			'field_fmt'				=> $field_fmt,
			'field_dt'				=> $field_dt,
			'field_data'			=> $field_data,
			'field_name'			=> $field_name
		);

		$ft_settings = array();

		if (isset($info['field_settings']) && strlen($info['field_settings']))
		{
			$ft_settings = unserialize(base64_decode($info['field_settings']));
		}

		$settings = array_merge($info, $settings, $ft_settings);

		ee()->legacy_api->instantiate('channel_fields');
		ee()->api_channel_fields->set_settings($info['field_id'], $settings);

		return $settings;
	}
}


class FieldsCollection {

	protected $data = array();
	protected $formats = array();

	public function __construct()
	{

	}

	public function format($id, $value)
	{
		$this->formats[$id] = $value;
	}

	public function data($id, $value)
	{
		$this->data[$id] = $value;
	}

	public function get($id)
	{
		return array(
			$this->data[$id],
			$this->formats[$id]
		);
	}

	public function all()
	{
		return array_map(array($this, 'get'), array_keys($this->data));
	}
}