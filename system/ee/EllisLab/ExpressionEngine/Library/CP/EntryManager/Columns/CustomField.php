<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\CP\EntryManager\Columns;

use EllisLab\ExpressionEngine\Library\CP\EntryManager\Columns\Column;
use EllisLab\ExpressionEngine\Model\Channel\ChannelField;
use EllisLab\ExpressionEngine\Model\Content\FieldFacade;

/**
 * Custom Field Column
 */
class CustomField extends Column
{
	private $field;

	public function __construct($identifier, ChannelField $channel_field)
	{
		parent::__construct($identifier);

		$this->field = $channel_field;
	}

	public function getTableColumnLabel()
	{
		return $this->field->field_label;
	}

	public function getTableColumnConfig()
	{
		return $this->getField()->getTableColumnConfig();
	}

	public function renderTableCell($entry)
	{
		if ($field = $this->getFieldForEntry($entry))
		{
			return $field->renderTableCell($field->getData());
		}

		return '';
	}

	/**
	 * Gets a generic FieldFacade object, not based on an entry
	 *
	 * @return FieldFacade
	 */
	private function getField()
	{
		$field = new FieldFacade($this->field->getId(), $this->field->getValues());
		$field->initField();

		return $field;
	}

	/**
	 * Gets a FieldFacade object initialized with entry data and details, but a
	 * lighter-weight method that going through getCustomField()
	 *
	 * @return FieldFacade
	 */
	private function getFieldForEntry($entry)
	{
		$field_name = $entry->getCustomFieldPrefix() . $this->field->getId();

		$field = $this->getField();
		$field->setContentId($entry->getId());
		$field->setData($entry->$field_name);

		return $field;
	}
}
