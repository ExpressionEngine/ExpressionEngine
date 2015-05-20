<?php

namespace EllisLab\ExpressionEngine\Model\Content\Display;

use EllisLab\ExpressionEngine\Service\Validation\Result;

class LayoutTab {

	public $id;
	public $title;

	protected $fields;
	protected $visible = TRUE;

	public function __construct($id, $title, array $fields = array())
	{
		$this->id = $id;
		$this->title = $title;
		$this->fields = $fields;
		return $this;
	}

	public function setFields($fields)
	{
		$this->fields = $fields;
		return $this;
	}

	public function addField($field)
	{
		$this->fields[] = $field;
		return $this;
	}

	public function getFields()
	{
		return $this->fields;
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

	public function hasErrors(Result $errors)
	{
		if ($errors->isValid())
		{
			return FALSE;
		}

		foreach ($this->fields as $field)
		{
			if ($errors->hasErrors($field->getName()))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

}