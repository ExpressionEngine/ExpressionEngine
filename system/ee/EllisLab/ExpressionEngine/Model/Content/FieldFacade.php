<?php

namespace EllisLab\ExpressionEngine\Model\Content;

class FieldFacade {

	private $id;
	private $data; // field_id_*
	private $format;  // field_ft_*
	private $timezone; // field_dt_*
	private $metadata;
	private $required;
	private $field_name;
	private $content_id;
	private $content_type;
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

	public function setContentType($type)
	{
		$this->content_type = $type;
	}

	public function getContentType($type)
	{
		return $this->content_type;
	}

	public function setTimezone($tz)
	{
		$this->timezone = $timezone;
	}

	public function getTimezone()
	{
		return $this->timezone;
	}

	protected function ensurePopulatedDefaults()
	{
		if ($callback = $this->getItem('populateCallback'))
		{
			$this->setItem('populateCallback', NULL);
			call_user_func($callback, $this);
		}
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function getData()
	{
		$this->ensurePopulatedDefaults();
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

	public function isRequired()
	{
		return $this->getItem('field_required') === 'y';
	}

	public function getItem($field)
	{
		if (array_key_exists($field, $this->metadata))
		{
			return $this->metadata[$field];
		}

		return NULL;
	}

	public function setItem($field, $value)
	{
		$this->metadata[$field] = $value;
	}

	public function getType()
	{
		return $this->getItem('field_type');
	}

	public function getTypeName()
	{
		ee()->legacy_api->instantiate('channel_fields');
		$fts = ee()->api_channel_fields->fetch_all_fieldtypes();
		$type = $this->getType();
		return $fts[$type]['name'];
	}

	public function validate($value)
	{
		$this->initField();
		$result = ee()->api_channel_fields->apply('validate', array($value));

		if (is_array($result))
		{
			if (isset($result['value']))
			{
				$this->setData($result['value']);
				$result = TRUE;
			}

			if (isset($result['error']))
			{
				$result = $result['error'];
			}
		}

		if (is_string($result) && strlen($result) > 0)
		{
			return $result;
		}

		return TRUE;
	}

	public function save()
	{
		$this->ensurePopulatedDefaults();

		$value = $this->data;
		$this->initField();
		return $this->data = ee()->api_channel_fields->apply('save', array($value));
	}

	public function getForm()
	{
		$data = $this->initField();

		$field_value = $data['field_data'];

		return ee()->api_channel_fields->apply('display_publish_field', array($field_value));
	}

	public function getSettingsForm()
	{
		ee()->load->library('table');
		$data = $this->initField();
		$out = ee()->api_channel_fields->apply('display_settings', array($data));

		if ($out == '')
		{
			return ee()->table->rows;
		}

		return $out;
	}

	public function saveSettingsForm($data)
	{
		$this->initField();
		return ee()->api_channel_fields->apply('save_settings', array($data));
	}

	public function getStatus()
	{
		$data = $this->initField();
		// initField can sometimes return a string if the field has a
		// string_override key.

		$field_value = set_value(
			$this->getName(),
			is_string($data) ? $data : $data['field_data']
		);

		return ee()->api_channel_fields->apply('get_field_status', array($field_value));
	}


	// TODO THIS WILL MOST DEFINITELY GO AWAY! BAD DEVELOPER!
	public function getNativeField()
	{
		$data = $this->initField();
		return ee()->api_channel_fields->setup_handler($this->getType(), TRUE);
	}


	public function initField()
	{
		$this->ensurePopulatedDefaults();

		$data = $this->setupField();

		ee()->api_channel_fields->setup_handler($data['field_id']);
		ee()->api_channel_fields->apply('_init', array(array(
			'content_id' => $this->content_id,
			'content_type' => $this->content_type
		)));

		return $data;
	}

	protected function setupField()
	{
		$field_dt = $this->timezone;
		$field_fmt = $this->format;
		$field_data = $this->data;
		$field_name = $this->getName();

		// not all custom field tables will specify all of these things
		$defaults = array(
			'field_instructions' => '',
			'field_text_direction' => 'rtl',
			'field_settings' => array()
		);

		$info = $this->metadata;
		$info = array_merge($defaults, $info);

		$settings = array(
			'field_instructions'	=> trim($info['field_instructions']),
			'field_text_direction'	=> ($info['field_text_direction'] == 'rtl') ? 'rtl' : 'ltr',
			'field_fmt'				=> $field_fmt,
			'field_dt'				=> $field_dt,
			'field_data'			=> $field_data,
			'field_name'			=> $field_name
		);

		$settings = array_merge($info, $settings, $info['field_settings']);

		ee()->legacy_api->instantiate('channel_fields');
		ee()->api_channel_fields->set_settings($info['field_id'], $settings);

		return $settings;
	}
}
