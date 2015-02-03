<?php

namespace EllisLab\ExpressionEngine\Model\Content\Display;

class FieldDisplay {

	protected $field;

	public function __construct($field)
	{
		$this->field = $field;
	}

	public function getType()
	{
		return $this->field->getItem('field_type');
	}

	public function getName()
	{
		return $this->field->getItem('field_name');
	}

	public function getLabel()
	{
		return $this->field->getItem('field_label');
	}

	public function getForm()
	{
		return $this->field->getForm();
	}

	public function getFormat()
	{
		return $this->field->getFormat();
	}

	public function getInstructions()
	{
		return $this->field->getItem('field_instructions');
	}

	public function isRequired()
	{
		return $this->field->getItem('field_required') == 'y';
	}
}