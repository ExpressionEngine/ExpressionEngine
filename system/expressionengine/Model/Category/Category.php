<?php
namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Model as Model;

class Category extends Model implements Content {
	protected static $meta = array(
		'primary_key' => 'cat_id',
		'entity_names' => array('CategoryEntity', 'CategoryFieldDataEntity'),
		'key_map' => array(
		)
	);
	
	protected $fields = array();

	/**
	 *
	 */
	public function getFields()
	{
		if ( empty($this->fields) && $this->getId() !== NULL)
		{
			$field_structures = $this->getContentStructure()
				->getFieldStructures();

			foreach ($field_structures as $field_structure)
			{
				$fields[$field_structure->field_id] = new CategoryFieldContent(
					$field_sturcture
					$this->entities['CategoryFieldDataEntity'],
				);
			}
		}

		return $this->fields;	
	}

	/**
	 * A link back to the owning channel object.
	 *
	 * @return	Structure	A link to the Structure objects that defines this
	 * 						Content's structure.
	 */
	public function getContentStructure()
	{
		return $this->getCategoryStructure();
	}

	public function getCategoryStructure()
	{
		return $this->manyToOne('CategoryStructure', 'cat_id', 'cat_id');
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
		// call render on all custom fields
	}


	/**
	 * Validates the channel entry before saving
	 *
	 * @return	void
	 *
	 * @throws	ContentInvalidException If content fails to validate a
	 * 						ContentInvalidException will be thrown with errors.
	 */
	public function validate()
	{

	}

}
