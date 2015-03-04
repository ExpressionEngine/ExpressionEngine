<?php

namespace EllisLab\ExpressionEngine\Model\Content\Display;

class FieldDisplay {

	protected $field;
	protected $collapsed = FALSE;
	protected $visible = TRUE;

	public function __construct($field)
	{
		$this->field = $field;
	}

	public function getType()
	{
		return $this->field->getItem('field_type');
	}

	public function getTypeName()
	{
		return $this->field->getTypeName();
	}

	public function getName()
	{
		return $this->field->getName();
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

	public function collapse()
	{
		$this->collapse = TRUE;
	}

	public function expand()
	{
		$this->collapse = FALSE;
	}

	public function isCollapsed()
	{
		return $this->collapsed;
	}
	public function hide()
	{
		$this->visible = FALSE;
		return $this;
	}

	public function show()
	{
		$this->visible = TRUE;
		return $this;
	}

	public function isVisible()
	{
		return $this->visible;
	}

	public function getSetting($item)
	{
		$settings = $this->field->initField();
		return $settings[$item];
	}

}