<?php
namespace EllisLab\ExpressionEngine\Model\DataTableField;

use EllisLab\ExpressionEngine\Model\Interfaces\Field\FieldContent as FieldContent;

abstract class DataTableFieldContent implements FieldContent {
	protected $entity = NULL;
	protected $structure = NULL;

	public function __construct($structure, $entity)
	{
		$this->structure = $structure;
		$this->entity = $entity;
	}

	/**
	 * A link back to the FieldStructure that describes the structure of this
	 * piece of FieldContent.
	 *
	 * @return	FieldStructure	The FieldStructure object that describes this
	 *						FieldContent's structure (and stores its settings.)
	 */
	public function getFieldStructure() 
	{
		return $this->structure;
	}

	/**
	 * Save this piece of field content to the database.
	 *
	 * @return	void
	 *
	 * @throws	FieldContentInvalidException	If validation fails, then a 
	 * 						FieldContentInvalidException will be thrown with
	 * 						errors.
	 */
	public function save() 
	{
		$this->entity->save();
	}

	/**
	 * Delete this piece of FieldContent from the database.
	 * 
	 * @return void
  	 */
	public function delete() 
	{
		throw new Exception('Cannot delete Channel Field Content, must delete Entry.');
	}

}
