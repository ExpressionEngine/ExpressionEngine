<?php

namespace EllisLab\ExpressionEngine\Model\Content;

class FieldFacade {

	private $id;
	private $data; // field_id_*
	private $format;  // field_ft_*
	private $timezone; // field_dt_*
	private $metadata;
	private $field_name;
	private $content_id;
	private $value;

	public function __construct($field_id, array $metadata)
	{
		$this->id = $field_id;
		$this->metadata = $metadata;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setName($name)
	{
		$this->field_name = $name;
	}

	public function getName()
	{
		return $this->field_name;
	}

	public function setContentId($id)
	{
		$this->content_id = $id;
	}

	public function getContentId()
	{
		return $this->content_id;
	}

	public function setTimezone($tz)
	{
		$this->timezone = $timezone;
	}

	public function getTimezone()
	{
		return $this->timezone;
	}

	public function set($data)
	{
		return $this->data = $this->save($data);
	}

	// sets the raw values as in the db. data coming form
	// the field post array should pass through setValue
	public function setData($data)
	{
		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setFormat($format)
	{
		$this->format = $format;
	}

	public function getFormat()
	{
		return $this->format;
	}

	public function getItem($field)
	{
		return $this->metadata[$field];
	}

	public function setItem($field, $value)
	{
		$this->metadata[$field] = $value;
	}

	public function getTypeName()
	{
		ee()->legacy_api->instantiate('channel_fields');
		$fts = ee()->api_channel_fields->fetch_all_fieldtypes();
		$type = $this->getItem('field_type');
		return $fts[$type]['name'];
	}

	public function save($value)
	{
		$this->initField();
		return ee()->api_channel_fields->apply('save', array($value));
	}

	public function getForm()
	{
		$data = $this->initField();

		// initField can sometimes return a string if the field has a
		// string_override key.
		if (is_string($data))
		{
			return $data;
		}

		$field_value = set_value($this->getName(), $data['field_data']);

		return ee()->api_channel_fields->apply('display_publish_field', array($field_value));
	}


	public function initField()
	{
		$data = $this->setupField();

		if (isset($data['string_override']))
		{
			return $data['string_override'];
		}

		ee()->api_channel_fields->setup_handler($data['field_id']);
		ee()->api_channel_fields->apply('_init', array(array(
			'content_id' => $this->content_id
		)));

		return $data;
	}

	protected function setupField()
	{
		$field_dt = $this->timezone;
		$field_fmt = $this->format;
		$field_data = $this->data;
		$field_name = $this->getName();

		$info = $this->metadata;

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