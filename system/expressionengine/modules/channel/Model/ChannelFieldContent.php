<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\Interfaces\Field\FieldContent 
	as FieldContent;

class ChannelFieldContent implements FieldContent {
	protected $entity = NULL;
	protected $structure = NULL;

	public function __construct($structure, $entity)
	{
		$this->structure = $structure;
		$this->entity = $entity;
	}

	/**
	 * Renders this field's content by replacing tags in a template.
	 *
	 * @param	ParsedTemplate|string	$template	The template, either a
	 *						ParsedTemplate object or a tagdata string, in which
	 *						this FieldContent will be rendered.
	 *
	 * @return	ParsedTemplate|string	The ParsedTemplate or tagdata string
	 *						with the relevant tags replaced.
	 */	
	public function render($template) 
	{

	}

	/**
	 * A link back to the FieldStructure that describes the structure of this
	 * piece of FieldContent.
	 *
	 * @return	FieldStructure	The FieldStructure object that describes this
	 *						FieldContent's structure (and stores its settings.)
	 */
	public function getStructure() 
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
	public function save() {
		$this->entity->save();
	}

	/**
	 * Validate this piece of field content to ensure that it is valid for
	 * saving.  On failure, throw an exception containing all error 
	 * information.
 	 * 
	 * @return void
	 * 
 	 * @throws	FieldContentInvalidException	On validation failure a 
	 * 						FieldContentInvalidException will be thrown with 
	 * 						all relevant errors.
	 */
	public function validate() {
		// TODO	
	}

	/**
	 * Delete this piece of FieldContent from the database.
	 * 
	 * @return void
  	 */
	public function delete() {
		throw new Exception('Cannot delete Channel Field Content, must delete Entry.');
	}

}
