<?php
namespace EllisLab\ExpressionEngine\Model\DataTableField;

use EllisLab\ExpressionEngine\Service\Model\Interfaces\Field\FieldContent;

abstract class DataTableFieldContent implements FieldContent {
	protected $gateway = NULL;
	protected $structure = NULL;

	public function __construct($structure, $gateway)
	{
		$this->structure = $structure;
		$this->gateway = $gateway;
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
		$this->gateway->save();
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
